<?php

namespace App\Console\Commands;

use App\Services\SMPP\SmppConnectionManager;
use App\Services\SMPP\SmppDispatcher;
use App\Services\SMPP\SmppEnvioService;
use App\Services\SMPP\SmppRateLimiter;
use App\Services\SMPP\SmppRecuperacaoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SmppWorker extends Command
{
  
    protected $signature = 'smpp:worker {--diagnostico} {--simular-envio}';

    protected $description = 'Worker SMPP Movitel - INFORDATA SMS';

    public function handle(
        SmppConnectionManager $manager,
        SmppDispatcher $dispatcher,
        SmppRecuperacaoService $recuperacaoService,
        SmppEnvioService $envioService,
        SmppRateLimiter $rateLimiter
    ): int {

        /*
        |--------------------------------------------------------------------------
        | SIMULAÇÃO DE ENVIO
        |--------------------------------------------------------------------------
        |
        | Não estabelece ligação SMPP.
        | Não altera SMS, reservas ou tentativas.
        | Não consome TPS.
        |
        */
        if ($this->option('simular-envio')) {

            $this->info('INFORDATA SMS - Simulação de envio');
            $this->line('----------------------------------');

            $sms = $envioService->proximaSms();

            if (!$sms) {
                $this->info('Nenhuma SMS elegível encontrada.');

                return self::SUCCESS;
            }

            try {
                $envioService->preparar((int) $sms->id);

                $this->info('SMS validada para preparação.');

                $this->table(
                    ['Campo', 'Valor'],
                    [
                        ['SMS ID', $sms->id],
                        ['Conta ID', $sms->conta_id],
                        ['Telefone', $sms->telefone],
                        ['Segmentos', $sms->segmentos],
                        ['Estado', $sms->estado],
                    ]
                );

                $this->warn(
                    'Simulação concluída. Nenhuma SMS foi enviada.'
                );

                return self::SUCCESS;

            } catch (Throwable $e) {

                $this->error(
                    'SMS não elegível: ' . $e->getMessage()
                );

                return self::FAILURE;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 1. MODO DIAGNÓSTICO
        |--------------------------------------------------------------------------
        |
        | Mantemos o diagnóstico sem processamento de SMS.
        |
        */
        if ($this->option('diagnostico')) {

            $this->info('INFORDATA SMS - Diagnóstico SMPP');
            $this->line('--------------------------------');

            $lock = Cache::store('redis')->lock('smpp:movitel:worker', 120);

            if (!$lock->get()) {
                $this->warn('Já existe um worker SMPP activo.');

                return self::FAILURE;
            }

            try {
                $connection = $manager->getConnection();

                if (!$connection->isBound()) {
                    throw new RuntimeException(
                        'A sessão SMPP não foi autenticada.'
                    );
                }

                $this->info('Autenticação SMPP: OK');
                $this->line('IP activo: ' . $manager->activeHost());
                $this->line('Sessão: ' . $manager->sessionId());
                $this->info('Nenhuma SMS foi enviada.');

                return self::SUCCESS;

            } catch (Throwable $e) {

                $this->error(
                    'Falha no diagnóstico SMPP: ' . $e->getMessage()
                );

                return self::FAILURE;

            } finally {

                try {
                    $manager->disconnect();
                } finally {
                    $lock->release();
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 2. BLOQUEIO EXCLUSIVO REDIS
        |--------------------------------------------------------------------------
        |
        | Impede dois workers desta aplicação de estabelecerem
        | sessões SMPP simultaneamente.
        |
        | Esta versão executa apenas um ciclo limitado,
        | ainda não é um daemon de produção.
        |
        */
        $lock = Cache::store('redis')->lock('smpp:movitel:worker', 120);

        if (!$lock->get()) {

            $this->warn(
                'Já existe um worker SMPP activo.'
            );

            return self::FAILURE;
        }

        $sessionId = null;
        $sessaoEncerrada = false;
        $resultado = self::FAILURE;

        try {

            /*
            |--------------------------------------------------------------------------
            | 3. ESTABELECER LIGAÇÃO
            |--------------------------------------------------------------------------
            */
            $manager->getConnection();

            $sessionId = $manager->sessionId();

            if (!$sessionId) {
                throw new RuntimeException(
                    'Sessão SMPP não identificada.'
                );
            }

            $dispatcher->clearSessionState();

            $this->info(
                'Sessão SMPP estabelecida.'
            );

            $this->line(
                'IP activo: ' . $manager->activeHost()
            );

            Log::info('Worker SMPP iniciado', [
                'session_id' => $sessionId,
                'host' => $manager->activeHost(),
                'worker_id' => $manager->workerId(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | 4. CICLO DE RECEPÇÃO
            |--------------------------------------------------------------------------
            |
            | O dispatcher agora processa directamente:
            |
            | - enquire_link
            | - enquire_link_resp
            | - submit_sm_resp
            | - deliver_sm
            | - unbind
            |
            | Não precisamos de callbacks aqui.
            |
            */
            $deadline = microtime(true) + 60;

            while (microtime(true) < $deadline) {

                /*
                 * Ler e processar a próxima PDU.
                 *
                 * readPdu() devolve null quando ainda
                 * não existe uma PDU completa.
                 */
                $dispatcher->processNext();

                /*
                 * Manter a sessão activa.
                 */
                $dispatcher->heartbeatIfNeeded();

                /*
                 * Detectar heartbeats sem resposta.
                 */
                $dispatcher->assertHeartbeatHealthy();
            }

            /*
            |--------------------------------------------------------------------------
            | 5. TERMINAR CICLO
            |--------------------------------------------------------------------------
            */
            $this->info(
                'Ciclo SMPP terminado.'
            );

            $resultado = self::SUCCESS;

        } catch (Throwable $e) {

            $this->error(
                'Erro no worker SMPP: ' . $e->getMessage()
            );

            Log::error('Erro no worker SMPP', [
                'session_id' => $sessionId,
                'erro' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            $resultado = self::FAILURE;

        } finally {

            /*
            |--------------------------------------------------------------------------
            | 6. ENCERRAR SESSÃO
            |--------------------------------------------------------------------------
            */
            try {

                $manager->disconnect();

                $sessaoEncerrada = true;

            } catch (Throwable $e) {

                Log::error(
                    'Falha ao encerrar sessão SMPP',
                    [
                        'session_id' => $sessionId,
                        'erro' => $e->getMessage(),
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 7. RECUPERAR TENTATIVAS SEM RESPOSTA
            |--------------------------------------------------------------------------
            |
            | Apenas depois de encerrar a sessão.
            |
            | Não libertamos créditos nem reenviamos SMS.
            |
            */
            if ($sessaoEncerrada && $sessionId) {

                try {

                    $recuperacaoService->recuperarSessao(
                        $sessionId
                    );

                } catch (Throwable $e) {

                    Log::error(
                        'Falha na recuperação SMPP',
                        [
                            'session_id' => $sessionId,
                            'erro' => $e->getMessage(),
                        ]
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | 8. LIMPAR ESTADO DO DISPATCHER
            |--------------------------------------------------------------------------
            */
            $dispatcher->clearSessionState();

            /*
            |--------------------------------------------------------------------------
            | 9. LIBERTAR BLOQUEIO REDIS
            |--------------------------------------------------------------------------
            */
            try {

                $lock->release();

            } catch (Throwable $e) {

                Log::error(
                    'Falha ao libertar bloqueio Redis SMPP',
                    [
                        'erro' => $e->getMessage(),
                    ]
                );
            }
        }

        return $resultado;
    }
}
