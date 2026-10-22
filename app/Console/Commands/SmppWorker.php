<?php

namespace App\Console\Commands;

use App\Models\Sms;
use App\Services\SMPP\SmppConnectionManager;
use App\Services\SMPP\SmppDispatcher;
use App\Services\SMPP\SmppEnvioService;
use App\Services\SMPP\SmppRateLimiter;
use App\Services\SMPP\SmppRecuperacaoService;
use App\Services\SMPP\SmppTentativaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SmppWorker extends Command
{
    protected $signature = 'smpp:worker-teste
        {--diagnostico : Testar apenas a ligação SMPP}
        {--simular-envio : Verificar uma SMS sem enviar}
        {--receber-dlr : Receber DLR sem enviar SMS}
        {--duracao=180 : Duração da sessão em segundos}
        {--limite=5 : Número máximo de SMS por lote}
        {--confirmar : Autorizar envios reais}
        {--servico : Executar um ciclo controlado de envio e recepção}';

    protected $description = 'Worker SMPP Movitel - envio controlado e recepção de DLR';

    public function handle(
        SmppConnectionManager $manager,
        SmppDispatcher $dispatcher,
        SmppRecuperacaoService $recuperacaoService,
        SmppEnvioService $envioService,
        SmppRateLimiter $rateLimiter,
        SmppTentativaService $tentativaService
    ): int {

        $limite = (int) $this->option('limite');
        $modoServico = (bool) $this->option('servico');
        $somenteDlr = (bool) $this->option('receber-dlr');
        $duracao = (int) $this->option('duracao');

        if ($duracao < 1 || $duracao > 180) {
            $this->error('A duração deve estar entre 1 e 180 segundos (lock de 300s).');
            return self::FAILURE;
        }

        if ($modoServico && ($somenteDlr || $this->option('diagnostico') || $this->option('simular-envio'))) {
            $this->error('O modo --servico não pode ser combinado com os modos de teste.');
            return self::FAILURE;
        }

        if ($modoServico && !$this->option('confirmar')) {
            $this->error('O modo --servico requer --confirmar para autorizar envios reais.');
            return self::FAILURE;
        }

        if ($limite < 1 || $limite > 20) {
            $this->error('O limite deve estar entre 1 e 20.');
            return self::FAILURE;
        }

        if ($this->option('simular-envio')) {
            $sms = $envioService->proximaSms();
            if (!$sms) {
                $this->info('Nenhuma SMS pendente elegível.');
                return self::SUCCESS;
            }
            try {
                $envioService->preparar((int) $sms->id);
                $this->info("SMS #{$sms->id} validada.");
                $this->warn('Nenhuma SMS foi enviada.');
                return self::SUCCESS;
            } catch (Throwable $e) {
                $this->error($e->getMessage());
                return self::FAILURE;
            }
        }

        if (!$this->option('diagnostico') && !$this->option('receber-dlr') && !$this->option('confirmar')) {
            $this->error(
                'Envio real não autorizado. Utilize --confirmar.'
            );
            return self::FAILURE;
        }

        $lock = Cache::store('redis')->lock('smpp:movitel:worker', 300);

        if (!$lock->get()) {
            $this->error('Já existe uma sessão SMPP activa.');
            return self::FAILURE;
        }

        $sessionId = null;
        $enviadas = 0;
        $rejeitadas = 0;
        $ignoradas = 0;
        $resultado = self::SUCCESS;

        // Sessão curta: 180s no máximo, incluindo conexão e submissão.
        $deadlineServico = $modoServico ? microtime(true) + $duracao : null;

        try {

            $connection = $manager->getConnection();
            $sessionId = $manager->sessionId();

            if (!$sessionId || !$connection->isBound()) {
                throw new RuntimeException(
                    'Sessão SMPP não autenticada.'
                );
            }

            $dispatcher->clearSessionState();

            $this->info('Sessão SMPP estabelecida.');
            $this->line('Host: ' . $manager->activeHost());
            $this->line('Sessão: ' . $sessionId);
            
            if ($this->option('diagnostico')) {
                $this->info('Diagnóstico concluído.');
                return self::SUCCESS;
            }
            /*
            |--------------------------------------------------------------------------
            | RECEPÇÃO DE DLR SEM ENVIO DE SMS
            |--------------------------------------------------------------------------
            */
            if ($this->option('receber-dlr')) {
                $duracao = (int) $this->option('duracao');
                if ($duracao < 1 || $duracao > 240) {
                    throw new RuntimeException(
                        'A duração deve estar entre 1 e 240 segundos.'
                    );
                }
                $this->info(
                    "A receber DLRs durante {$duracao} segundos..."
                );
                $deadline = microtime(true) + $duracao;
                while (microtime(true) < $deadline) {
                    $dispatcher->processNext();
                    $dispatcher->heartbeatIfNeeded();
                    $dispatcher->assertHeartbeatHealthy();
                }
                $this->info('Recepção de DLR concluída.');
                return self::SUCCESS;
            }
            /*
             * O lote é limitado e a sessão é exclusiva.
             * Não fazemos polling indefinido.
             */
            $ids = DB::table('sms_outbox as o')
                ->join('sms as s', 's.id', '=', 'o.sms_id')
                ->where('o.estado', 'PENDENTE')
                ->where('s.estado', 'PENDENTE')
                ->whereNotExists(function ($query) {
                    $query->selectRaw('1')
                        ->from('smpp_tentativas_envio as t')
                        ->whereColumn('t.sms_id', 's.id');
                })
                ->orderBy('o.id')
                ->limit($modoServico ? 1 : $limite)
                ->pluck('s.id')
                ->unique()
                ->values();
            $this->info(
                'SMS seleccionadas: ' . $ids->count()
            );
            foreach ($ids as $smsId) {
                // Nunca iniciar uma submissão se o tempo de sessão for insuficiente.
                // Reservamos 100s para submit_sm_resp (90s) e encerramento.
                if ($modoServico && microtime(true) + 100 >= $deadlineServico) {
                    $this->warn('Tempo insuficiente para nova submissão; ciclo encerrado.');
                    break;
                }
                $smsId = (int) $smsId;
                $tentativaId = null;
                $submissaoIniciada = false;
                try {
                    /*
                     * Validar SMS e reservas.
                     */
                    $sms = $envioService->preparar($smsId);
                    /*
                     * Validar Sender ID.
                     */
                    $senderRegisto = DB::table('sender_ids')
                        ->where('id', $sms->sender_id)
                        ->first();
                    if (!$senderRegisto) {
                        throw new RuntimeException(
                            'Sender ID não encontrado.'
                        );
                    }
                    if (
                        $senderRegisto->estado !== 'APROVADO'
                        || $senderRegisto->operadora !== 'MOVITEL'
                        || !$senderRegisto->aprovado_operadora_em
                    ) {
                        throw new RuntimeException(
                            'Sender ID não aprovado pela Movitel.'
                        );
                    }
                    $sender = (string) $senderRegisto->sender;
                    if (!preg_match(
                        '/^[A-Za-z0-9]{1,11}$/',
                        $sender
                    )) {
                        throw new RuntimeException(
                            'Sender ID inválido.'
                        );
                    }
                    /*
                     * Normalizar telefone.
                     */
                    $telefone = preg_replace(
                        '/\D+/',
                        '',
                        (string) $sms->telefone
                    );
                    if (preg_match(
                        '/^8[2-7][0-9]{7}$/',
                        $telefone
                    )) {
                        $telefone = '258' . $telefone;
                    }
                    if (!preg_match(
                        '/^2588[2-7][0-9]{7}$/',
                        $telefone
                    )) {
                        throw new RuntimeException(
                            'Telefone inválido.'
                        );
                    }
                    /*
                     * Esperar disponibilidade de TPS
                     * antes de criar a tentativa.
                     *
                     * O lote sequencial não deve
                     * atingir normalmente o limite.
                     */
                    $tpsDisponivel = false;
                    for ($i = 0; $i < 20; $i++) {
                        if ($rateLimiter->acquire()) {
                            $tpsDisponivel = true;
                            break;
                        }
                        usleep(100000);
                    }
                    if (!$tpsDisponivel) {
                        throw new RuntimeException(
                            'TPS indisponível.'
                        );
                    }
                    /*
                     * Preparar PDU em memória.
                     */
                    $sequence = $connection->prepareSubmit(
                        $sender,
                        $telefone,
                        (string) $sms->mensagem
                    );
                    /*
                     * Persistir tentativa.
                     */
                    $tentativaId =
                        $envioService->prepararTentativa(
                            smsId: $smsId,
                            sessionId: $sessionId,
                            sequenceNumber: $sequence,
                            host: (string) $manager->activeHost(),
                            porta: (int) config(
                                'services.smpp.port',
                                8014
                            ),
                            workerId: $manager->workerId(),
                            tentativaService: $tentativaService
                        );
                    /*
                     * Marcar como SUBMETENDO antes
                     * da escrita no socket.
                     */
                    $tentativaService->iniciarSubmissao(
                        $tentativaId
                    );
                    $submissaoIniciada = true;
                    /*
                     * Transmitir exactamente uma vez.
                     */
                    $connection->sendPreparedSubmit(
                        $sequence
                    );
                    $this->line(
                        "SMS #{$smsId}: PDU transmitida."
                    );
                    /*
                     * Aguardar confirmação.
                     */
                    $deadline = microtime(true) + 90;
                    $respostaRecebida = false;
                    while (microtime(true) < $deadline) {
                        $dispatcher->processNext();
                        $resposta = $dispatcher
                            ->takeSubmitResponse($sequence);
                        if ($resposta !== null) {
                            $respostaRecebida = true;
                            break;
                        }
                        $dispatcher->heartbeatIfNeeded();
                        $dispatcher->assertHeartbeatHealthy();
                    }
                    if (!$respostaRecebida) {
                        throw new RuntimeException(
                            'Sem resposta definitiva da Movitel.'
                        );
                    }
                    $smsActual = Sms::findOrFail($smsId);
                    if (in_array(
                        $smsActual->estado,
                        ['ENVIADO', 'ENTREGUE'],
                        true
                    )) {
                        $enviadas++;
                        $this->info(
                            "SMS #{$smsId}: ACEITE."
                        );
                    } else {
                        $rejeitadas++;
                        $this->warn(
                            "SMS #{$smsId}: {$smsActual->estado}."
                        );
                    }
                } catch (Throwable $e) {
                    Log::error('SMPP LOTE ERRO', [
                        'sms_id' => $smsId,
                        'tentativa_id' => $tentativaId,
                        'submissao_iniciada' => $submissaoIniciada,
                        'erro' => $e->getMessage(),
                    ]);
                    $this->error(
                        "SMS #{$smsId}: {$e->getMessage()}"
                    );
                    /*
                     * Depois de SUBMETENDO, não sabemos
                     * se a operadora recebeu a SMS.
                     * Interrompemos imediatamente o lote.
                     */
                    if ($submissaoIniciada) {
                        $resultado = self::FAILURE;
                        break;
                    }
                    /*
                     * Falhas antes da submissão não
                     * autorizam novas tentativas.
                     * Se uma tentativa já existe,
                     * paramos para análise.
                     */
                    if ($tentativaId !== null) {
                        $resultado = self::FAILURE;
                        break;
                    }
                    $ignoradas++;
                }
            }
            $this->newLine();
            $this->info('RESUMO DO LOTE');
            $this->line("Aceites: {$enviadas}");
            $this->line("Rejeitadas: {$rejeitadas}");
            $this->line("Ignoradas: {$ignoradas}");
            return $resultado;
        } catch (Throwable $e) {
            Log::error('SMPP WORKER LOTE ERRO', [
                'session_id' => $sessionId,
                'erro' => $e->getMessage(),
            ]);
            $this->error($e->getMessage());
            return self::FAILURE;
        } finally {
            /*
             * Encerrar antes de recuperar tentativas.
             */
            try {
                $manager->disconnect();
            } catch (Throwable $e) {
                report($e);
            }
            if ($sessionId) {
                try {
                    $recuperacaoService->recuperarSessao(
                        $sessionId
                    );
                } catch (Throwable $e) {
                    report($e);
                }
            }
            $dispatcher->clearSessionState();
            try {
                $lock->release();
            } catch (Throwable $e) {
                report($e);
            }
        }
    }
}
