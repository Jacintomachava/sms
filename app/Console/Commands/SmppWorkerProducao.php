<?php

namespace App\Console\Commands;

use App\Models\Sms;
use App\Services\SMPP\{SmppConnectionManager, SmppDispatcher, SmppEnvioService, SmppRateLimiter, SmppRecuperacaoService, SmppTentativaService};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{Cache, DB, Log};
use RuntimeException;
use Throwable;

/**
 * Worker de produção conservador: sessão única, polling contínuo,
 * uma submissão pendente de cada vez e ciclo de duração limitada.
 * NÃO é ainda a implementação pipelined de 20 submissões em trânsito.
 */
class SmppWorkerProducao extends Command
{
    protected $signature = 'smpp:worker {--duracao=180 : Duração máxima do ciclo (1-180 segundos)} {--confirmar : Autorizar submissões reais}';
    protected $description = 'Worker SMPP de produção com sessão exclusiva e recepção de DLR';
    private bool $stop = false;

    public function handle(
        SmppConnectionManager $manager,
        SmppDispatcher $dispatcher,
        SmppRecuperacaoService $recuperacao,
        SmppEnvioService $envio,
        SmppRateLimiter $limiter,
        SmppTentativaService $tentativas
    ): int {
        $duracao = (int) $this->option('duracao');
        if (!$this->option('confirmar') || $duracao < 1 || $duracao > 180) {
            $this->error('Use --confirmar e --duracao entre 1 e 180.');
            return self::FAILURE;
        }
        if (!extension_loaded('pcntl')) {
            $this->error('A extensão pcntl é obrigatória para encerramento seguro.');
            return self::FAILURE;
        }
        pcntl_async_signals(true);
        pcntl_signal(SIGTERM, fn () => $this->stop = true);
        pcntl_signal(SIGINT, fn () => $this->stop = true);

        // flock protege contra dois processos no mesmo host, mesmo se Redis expirar.
        $file = fopen(storage_path('framework/smpp-movitel-worker.lock'), 'c');
        if (!$file || !flock($file, LOCK_EX | LOCK_NB)) {
            $this->error('Já existe um worker SMPP neste servidor.');
            return self::FAILURE;
        }
        $lock = null;
        $sessionId = null;
        $exit = self::SUCCESS;
        $deadline = microtime(true) + $duracao;
        $pending = null;
        $ignoradas = [];
        try {
            $lock = Cache::store('redis')->lock('smpp:movitel:worker', 300);
            if (!$lock->get()) {
                throw new RuntimeException('Outra sessão SMPP detém o lock Redis.');
            }
            $connection = $manager->getConnection();
            $sessionId = $manager->sessionId();
            if (!$sessionId || !$connection->isBound()) {
                throw new RuntimeException('Sessão SMPP não autenticada.');
            }
            $dispatcher->clearSessionState();
            $this->info('SMPP activo. Sessão: ' . $sessionId);

            while (!$this->stop && microtime(true) < $deadline) {
                // O dispatcher confirma/rejeita financeiramente antes de devolver a resposta.
                try {
                    $dispatcher->processNext();
                } catch (Throwable $e) {
                    if ($this->stop) {
                        break; // SIGTERM pode interromper stream_select.
                    }
                    throw $e;
                }
                $dispatcher->heartbeatIfNeeded();
                $dispatcher->assertHeartbeatHealthy();

                if ($pending !== null) {
                    $response = $dispatcher->takeSubmitResponse($pending['seq']);
                    if ($response !== null) {
                        $this->line('Resposta da SMS #' . $pending['sms'] . ' recebida.');
                        $pending = null;
                    } elseif (microtime(true) > $pending['deadline']) {
                        throw new RuntimeException('Resposta SMPP incerta para SMS #' . $pending['sms']);
                    }
                    continue;
                }

                // Não iniciar nova submissão perto do fim do ciclo.
                if (microtime(true) + 100 >= $deadline) {
                    continue; // Ainda recebemos DLRs até ao encerramento.
                }
                $smsId = DB::table('sms_outbox as o')
                    ->join('sms as s', 's.id', '=', 'o.sms_id')
                    ->where('o.estado', 'PENDENTE')
                    ->where('s.estado', 'PENDENTE')
                    ->when($ignoradas !== [], fn ($q) => $q->whereNotIn('s.id', $ignoradas))
                    ->whereNotExists(function ($q) {
                        $q->selectRaw('1')->from('smpp_tentativas_envio as t')
                          ->whereColumn('t.sms_id', 's.id');
                    })
                    ->orderBy('o.id')->value('s.id');
                if (!$smsId || !$limiter->acquire()) {
                    usleep(100000);
                    continue;
                }
                $attemptId = null;
                try {
                    $sms = $envio->preparar((int) $smsId);
                    $sender = DB::table('sender_ids')->where('id', $sms->sender_id)->first();
                    if (!$sender || $sender->estado !== 'APROVADO'
                        || $sender->operadora !== 'MOVITEL' || !$sender->aprovado_operadora_em
                        || !preg_match('/^[A-Za-z0-9]{1,11}$/', (string) $sender->sender)) {
                        throw new RuntimeException('Sender ID não aprovado pela Movitel.');
                    }
                    $telefone = preg_replace('/\D+/', '', (string) $sms->telefone);
                    if (preg_match('/^8[2-7][0-9]{7}$/', $telefone)) {
                        $telefone = '258' . $telefone;
                    }
                    if (!preg_match('/^2588[2-7][0-9]{7}$/', $telefone)) {
                        throw new RuntimeException('Telefone inválido.');
                    }
                    if ($this->stop) {
                        break;
                    }
                    $seq = $connection->prepareSubmit((string) $sender->sender, $telefone, (string) $sms->mensagem);
                    $attemptId = $envio->prepararTentativa(
                        smsId: (int) $smsId, sessionId: $sessionId,
                        sequenceNumber: $seq, host: (string) $manager->activeHost(),
                        porta: (int) config('services.smpp.port', 8014),
                        workerId: $manager->workerId(), tentativaService: $tentativas
                    );
                    $tentativas->iniciarSubmissao($attemptId);
                    $connection->sendPreparedSubmit($seq);
                    $pending = ['sms' => (int) $smsId, 'seq' => $seq, 'deadline' => microtime(true) + 90];
                    $this->line('SMS #' . $smsId . ': PDU transmitida.');
                } catch (Throwable $e) {
                    Log::error('SMPP ENVIO PRODUCAO', ['sms_id' => $smsId, 'tentativa_id' => $attemptId, 'erro' => $e->getMessage()]);
                    if ($attemptId !== null) {
                        throw $e; // nunca reenviar tentativa com resultado incerto
                    }
                    // Falha pré-submissão: não bloquear toda a fila, mas não girar sobre a mesma SMS.
                    $this->warn('SMS #' . $smsId . ': ' . $e->getMessage());
                    $ignoradas[] = (int) $smsId;
                    usleep(500000);
                }
            }
        } catch (Throwable $e) {
            $exit = self::FAILURE;
            Log::error('SMPP WORKER PRODUCAO', ['session_id' => $sessionId, 'erro' => $e->getMessage()]);
            $this->error($e->getMessage());
        } finally {
            try { $manager->disconnect(); } catch (Throwable $e) { report($e); }
            if ($sessionId) {
                try { $recuperacao->recuperarSessao($sessionId); } catch (Throwable $e) { report($e); $exit = self::FAILURE; }
            }
            $dispatcher->clearSessionState();
            if ($lock) {
                try { $lock->release(); } catch (Throwable $e) { report($e); }
            }
            flock($file, LOCK_UN);
            fclose($file);
        }
        return $exit;
    }
}
