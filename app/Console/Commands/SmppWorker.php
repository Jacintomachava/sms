<?php

namespace App\Console\Commands;

use App\Services\SMPP\SmppConnectionManager;
use App\Services\SMPP\SmppDispatcher;
use App\Services\SMPP\SmppRespostaService;
use App\Services\SMPP\SmppRecuperacaoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SmppWorker extends Command
{
    protected $signature = 'smpp:worker {--diagnostico}';

    protected $description = 'Worker persistente SMPP Movitel';

    public function handle(
        SmppConnectionManager $manager,
        SmppDispatcher $dispatcher,
        SmppRespostaService $respostaService,
        SmppRecuperacaoService $recuperacaoService
    ): int {

        /*
        |--------------------------------------------------------------------------
        | 1. DIAGNÓSTICO
        |--------------------------------------------------------------------------
        */
        if ($this->option('diagnostico')) {
            $this->info(
                'Modo diagnóstico. Nenhuma SMS será enviada.'
            );

            return self::SUCCESS;
        }

        /*
        |--------------------------------------------------------------------------
        | 2. BLOQUEIO EXCLUSIVO
        |--------------------------------------------------------------------------
        |
        | O worker só mantém a sessão enquanto possuir o bloqueio.
        | Nesta versão, o ciclo é limitado a 60 segundos.
        |
        */
        $lock = Cache::store('redis')->lock(
            'smpp:movitel:worker',
            120
        );

        if (!$lock->get()) {
            $this->warn(
                'Já existe um worker SMPP activo.'
            );

            return self::FAILURE;
        }

        $sessionId = null;
        $connectionFailed = false;

        try {

            /*
            |--------------------------------------------------------------------------
            | 3. ESTABELECER SESSÃO
            |--------------------------------------------------------------------------
            */
            $manager->getConnection();

            $sessionId = $manager->sessionId();

            if (!$sessionId) {
                throw new RuntimeException(
                    'Sessão SMPP não identificada.'
                );
            }

            $this->info(
                'Ligação SMPP estabelecida: ' .
                $manager->activeHost()
            );

            /*
            |--------------------------------------------------------------------------
            | 4. CICLO DE RECEPÇÃO
            |--------------------------------------------------------------------------
            */
            $deadline = microtime(true) + 60;

            while (microtime(true) < $deadline) {

                $dispatcher->processNext(
                    persistDlr: function (array $pdu) {
                        Log::info(
                            'DLR SMPP recebido; persistência pendente',
                            [
                                'sequence' => $pdu['sequence'] ?? null,
                            ]
                        );
                    },

                    processSubmitResponse: function (array $pdu) use (
                        $manager,
                        $dispatcher,
                        $respostaService
                    ) {

                        $sessionId = $manager->sessionId();

                        if (!$sessionId) {
                            throw new RuntimeException(
                                'Sessão SMPP não identificada.'
                            );
                        }

                        $messageId =
                            $dispatcher->extractSubmitMessageId($pdu);

                        $respostaService->processar(
                            sessionId: $sessionId,
                            sequenceNumber: (int) $pdu['sequence'],
                            commandStatus: (int) $pdu['status'],
                            providerMessageId: $messageId
                        );
                    }
                );

                $dispatcher->heartbeatIfNeeded();
                $dispatcher->assertHeartbeatHealthy();
            }

            return self::SUCCESS;

        } catch (Throwable $e) {

            $this->error($e->getMessage());

            Log::error('Erro no worker SMPP', [
                'erro' => $e->getMessage(),
                'session_id' => $sessionId,
            ]);

            /*
            |--------------------------------------------------------------------------
            | IMPORTANTE
            |--------------------------------------------------------------------------
            |
            | Não presumimos que toda excepção seja uma falha de rede.
            | A classificação será adicionada na próxima etapa.
            |
            */
            $connectionFailed = false;

            return self::FAILURE;

        } finally {

            try {
                $manager->disconnect();
            } catch (Throwable $e) {
                Log::error(
                    'Falha ao desligar SMPP',
                    ['erro' => $e->getMessage()]
                );
            }

            if ($connectionFailed && $sessionId) {
                try {
                    $recuperacaoService->recuperarSessao(
                        $sessionId
                    );
                } catch (Throwable $e) {
                    Log::error(
                        'Falha ao recuperar sessão SMPP',
                        ['erro' => $e->getMessage()]
                    );
                }
            }

            $lock->release();
        }
    }
}