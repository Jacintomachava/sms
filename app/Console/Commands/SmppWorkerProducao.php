<?php

namespace App\Console\Commands;

use App\Services\SMPP\SmppConnectionManager;
use App\Services\SMPP\SmppDispatcher;
use App\Services\SMPP\SmppEnvioService;
use App\Services\SMPP\SmppRateLimiter;
use App\Services\SMPP\SmppRecuperacaoService;
use App\Services\SMPP\SmppTentativaService;
use App\Services\SMPP\SmppUdhSegmentador;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Uma sessão SMPP, submissões assíncronas em janela limitada e DLR na mesma ligação.
 * Ciclos limitados a 180s porque o lock Redis existente expira em 300s.
 * Supervisor reinicia o processo; não é permitido sobrepor workers.
 */
class SmppWorkerProducao extends Command
{
    protected $signature = 'smpp:worker
        {--confirmar : Autoriza transmissões reais e cobranças}
        {--duracao=180 : Segundos por sessão (máximo 180)}
        {--janela=10 : Máximo de submit_sm_resp pendentes (1 a 20)}
        {--timeout-resposta=60 : Prazo de resposta SMPP (10 a 90 segundos)}
        {--somente-dlr : Receber relatórios sem submeter novas SMS}';

    protected $description = 'Worker SMPP de produção com janela assíncrona, sessão exclusiva e DLR';
    private bool $parar = false;

    public function handle(
        SmppConnectionManager $manager,
        SmppDispatcher $dispatcher,
        SmppRecuperacaoService $recuperacao,
        SmppEnvioService $envio,
        SmppRateLimiter $limiter,
        SmppTentativaService $tentativas,
        SmppUdhSegmentador $segmentador
    ): int {

        $duracao = (int) $this->option('duracao');
        $janela = (int) $this->option('janela');
        $timeout = (int) $this->option('timeout-resposta');
        $somenteDlr = (bool) $this->option('somente-dlr');

        if ($duracao < 30 || $duracao > 180 || $janela < 1 || $janela > 20 || $timeout < 10 || $timeout > 90) {
            $this->error('Parâmetros inválidos: duração 30..180, janela 1..20, timeout 10..90.');
            return self::FAILURE;
        }

        if (!$somenteDlr && !$this->option('confirmar')) {
            $this->error('Envio real requer --confirmar.');
            return self::FAILURE;
        }

        if (function_exists('pcntl_async_signals')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGTERM, fn () => $this->parar = true);
            pcntl_signal(SIGINT, fn () => $this->parar = true);
        }

        // Exclusão compartilhada com smpp:worker-teste.
        // Após SIGKILL o antigo dono não executa finally(); o TTL do lock
        // continua válido. Esperar sem criar uma segunda sessão SMPP.
        // O lock é o MESMO utilizado pelo comando de testes.
        $lock = Cache::store('redis')->lock('smpp:movitel:worker', 300);
        $esperaAte = microtime(true) + 330;
        $adquirido = false;
        $ultimoAviso = 0.0;

        while (!$this->parar && microtime(true) < $esperaAte) {
            if ($lock->get()) {
                $adquirido = true;
                break;
            }
            if (microtime(true) - $ultimoAviso >= 30) {
                $this->warn('Sessão SMPP ocupada; aguardando libertação do bloqueio Redis.');
                $ultimoAviso = microtime(true);
            }
            usleep(500000);
        }

        if (!$adquirido) {
            if ($this->parar) {
                return self::SUCCESS;
            }
            $this->error('Bloqueio SMPP indisponível após 330 segundos.');
            return self::FAILURE;
        }

        $sessao = null;
        $pendentes = []; // sequence => ['sms_id', 'tentativa_id', 'enviado_em']
        $deadline = microtime(true) + $duracao;
        $resultado = self::SUCCESS;

        try {
            $recuperadas = $recuperacao->recuperarTentativasAbandonadas();
            if (!empty($recuperadas['erros'])) {
                throw new RuntimeException('Não foi possível reconciliar tentativas abandonadas.');
            }
            $connection = $manager->getConnection();
            $sessao = $manager->sessionId();
            if (!$sessao || !$connection->isBound()) {
                throw new RuntimeException('Sessão SMPP não autenticada.');
            }
            $dispatcher->clearSessionState();
            $this->info("SMPP activo. Sessão: {$sessao}; janela: {$janela}");

            // Conservador: apenas UMA SMS lógica em curso por vez.
            // A janela assíncrona continua disponível para os segmentos da mesma SMS.
            $activa = null;
            $ignoradas = [];
            while (!$this->parar && microtime(true) < $deadline) {
                $dispatcher->processNext();
                $dispatcher->heartbeatIfNeeded();
                $dispatcher->assertHeartbeatHealthy();

                foreach ($pendentes as $sequence => $info) {
                    $resposta = $dispatcher->takeSubmitResponse((int) $sequence);
                    if ($resposta !== null) {
                        unset($pendentes[$sequence]);
                        if ((int) $resposta['status'] !== 0) {
                            // Não transmitir outros segmentos após rejeição conhecida.
                            if ($activa !== null) {
                                $activa['falha'] = true;
                            }
                        }
                    } elseif (microtime(true) - $info['enviado_em'] > $timeout) {
                        throw new RuntimeException("Timeout submit_sm_resp da SMS #{$info['sms_id']}.");
                    }
                }

                if ($activa !== null && $activa['falha']) {
                    // A recuperação marcará como incertos os segmentos submetidos
                    // ainda sem resposta. PREPARADO permanece sem transmissão.
                    throw new RuntimeException('Rejeição parcial de segmentos; requer reconciliação.');
                }

                if ($activa !== null && $activa['indice'] >= count($activa['partes']) && !$pendentes) {
                    
                    $smsFinal = DB::table('sms')
                        ->where('id', $activa['sms_id'])
                        ->first();

                    if (!$smsFinal) {
                        throw new RuntimeException(
                            'SMS não encontrada após processamento SMPP.'
                        );
                    }

                    if (in_array($smsFinal->estado, [
                        'ENVIADO',
                        'ENTREGUE',
                        'REJEITADO',
                        'FALHADO',
                    ], true)) {

                        $this->info(
                            "SMS #{$activa['sms_id']}: finalizada com estado {$smsFinal->estado}."
                        );

                        $activa = null;

                    } elseif ($smsFinal->estado === 'INCERTO') {

                        $this->warn(
                            "SMS #{$activa['sms_id']}: resultado incerto; encaminhada para reconciliação."
                        );

                        $activa = null;

                    } else {

                        throw new RuntimeException(
                            "SMS #{$activa['sms_id']} terminou respostas, mas permanece no estado {$smsFinal->estado}."
                        );
                    }
                }

                if ($somenteDlr || microtime(true) + $timeout + 5 >= $deadline || count($pendentes) >= $janela) {
                    usleep(30000);
                    continue;
                }

                if ($activa === null) {
                    $sms = $envio->proximaSms($ignoradas);
                    if (!$sms) {
                        usleep(150000);
                        continue;
                    }
                    $tentativaId = null;
                    try {
                        $sms = $envio->preparar((int) $sms->id);
                        $senderRow = DB::table('sender_ids')->where('id', $sms->sender_id)->first();
                        if (!$senderRow || $senderRow->estado !== 'APROVADO' ||
                            $senderRow->operadora !== 'MOVITEL' || !$senderRow->aprovado_operadora_em) {
                            throw new RuntimeException('Sender ID não aprovado pela Movitel.');
                        }
                        $sender = (string) $senderRow->sender;
                        if (!preg_match('/^[A-Za-z0-9]{1,11}$/D', $sender)) {
                            throw new RuntimeException('Sender ID inválido.');
                        }
                        $telefone = preg_replace('/\D+/', '', (string) $sms->telefone);
                        if (preg_match('/^8[2-7][0-9]{7}$/D', $telefone)) {
                            $telefone = '258' . $telefone;
                        }
                        if (!preg_match('/^2588[2-7][0-9]{7}$/D', $telefone)) {
                            throw new RuntimeException('Telefone inválido.');
                        }

                        $referenciaUdh = random_int(0, 255);
                        $partes = $segmentador->preparar((string) $sms->mensagem, $referenciaUdh);
                        if (count($partes) !== (int) $sms->segmentos) {
                            throw new RuntimeException('Quantidade de segmentos incompatível com reservas.');
                        }
                        $sequences = [];
                        foreach ($partes as $parte) {
                            $sequences[] = [
                                'numero_segmento' => (int) $parte['numero'],
                                'sequence_number' => $connection->prepareSegmentSubmit($sender, $telefone, $parte),
                            ];
                        }
                        $tentativaId = (int) $tentativas->prepararSegmentos(
                            (int) $sms->id, $sessao, (string) $manager->activeHost(),
                            (int) config('services.smpp.port', 8014), $manager->workerId(),
                            $sequences, count($partes) > 1 ? $referenciaUdh : null
                        )->id;
                        $activa = [
                            'sms_id' => (int) $sms->id,
                            'tentativa_id' => $tentativaId,
                            'partes' => $partes,
                            'sequences' => $sequences,
                            'indice' => 0,
                            'falha' => false,
                        ];
                    } catch (Throwable $e) {
                        Log::error('SMPP falha ao preparar SMS', [
                            'sms_id' => $sms->id, 'tentativa_id' => $tentativaId,
                            'erro' => $e->getMessage(),
                        ]);
                        if ($tentativaId !== null) {
                            throw $e;
                        }
                        $ignoradas[] = (int) $sms->id;
                        $this->warn("SMS #{$sms->id} ignorada neste ciclo: {$e->getMessage()}");
                        continue;
                    }
                }

                // Cada PDU consome um token TPS, não cada SMS lógica.
                if (!$limiter->acquire()) {
                    usleep(60000);
                    continue;
                }
                $i = $activa['indice'];
                $parte = $activa['partes'][$i];
                $sequence = $activa['sequences'][$i]['sequence_number'];
                $tentativas->iniciarSegmento($activa['tentativa_id'], (int) $parte['numero']);
                // O estado SUBMETENDO foi persistido ANTES da transmissão.
                $connection->sendPreparedSubmit($sequence);
                $pendentes[$sequence] = [
                    'sms_id' => $activa['sms_id'],
                    'tentativa_id' => $activa['tentativa_id'],
                    'enviado_em' => microtime(true),
                ];
                $activa['indice']++;
                $this->line("SMS #{$activa['sms_id']}: segmento {$parte['numero']}/{$parte['total']} enviado (seq {$sequence}).");
            }

            // Se a sessão terminou com segmentos ainda não iniciados,
            // não permitir finalização silenciosa da tentativa.
            if ($activa !== null && $activa['indice'] < count($activa['partes'])) {
                throw new RuntimeException('Ciclo encerrado com SMS multipart incompleta.');
            }

            // Encerramento controlado: dar oportunidade às respostas pendentes.
            $drenagem = microtime(true) + $timeout;
            while ($pendentes && microtime(true) < $drenagem) {
                $dispatcher->processNext();
                $dispatcher->heartbeatIfNeeded();
                $dispatcher->assertHeartbeatHealthy();
                foreach ($pendentes as $seq => $info) {
                    if ($dispatcher->takeSubmitResponse((int) $seq) !== null) {
                        unset($pendentes[$seq]);
                    }
                }
            }

            if ($pendentes) {
                throw new RuntimeException('Sessão terminou com respostas SMPP pendentes.');
            }

        } catch (Throwable $e) {

            $resultado = self::FAILURE;
            Log::error('SMPP worker interrompido', ['erro' => $e->getMessage(), 'sessao' => $sessao]);
            $this->error($e->getMessage());
            $manager->reportFailure($e);

        } finally {
            // Depois de perder uma sessão, SUBMETENDO sem resposta fica INCERTO.
            if ($sessao) {
                try {
                    $r = $recuperacao->recuperarSessao($sessao);
                    if (!empty($r['erros'])) {
                        $resultado = self::FAILURE;
                        Log::critical('SMPP falha ao reconciliar sessão', ['erros' => $r['erros']]);
                    }
                } catch (Throwable $e) {
                    $resultado = self::FAILURE;
                    Log::critical('SMPP falha na recuperação final', ['erro' => $e->getMessage()]);
                }
            }

            $manager->disconnect();
            $dispatcher->clearSessionState();
            try { $lock->release(); } catch (Throwable $e) {
                Log::critical('SMPP falha ao libertar lock', ['erro' => $e->getMessage()]);
                $resultado = self::FAILURE;
            }
        }
        
        return $resultado;
    }
}
