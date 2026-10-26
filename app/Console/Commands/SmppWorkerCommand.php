<?php

namespace App\Console\Commands;

use App\Services\SMPP\SmppConnectionManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class SmppWorkerCommand extends Command
{
    protected $signature = 'smpp:worker-legado
        {--diagnostico : Apenas verifica a sessão SMPP}';

    protected $description =
        'Worker persistente de comunicação SMPP com a Movitel';

    private bool $running = true;

    public function handle(
        SmppConnectionManager $manager
    ): int {
        $this->info('INFORDATA SMS - Worker SMPP');
        $this->line('----------------------------');

        /*
         * Por segurança, esta primeira versão
         * não retira SMS da fila nem submete mensagens.
         */
        if (!$this->option('diagnostico')) {
            $this->warn(
                'Envio desactivado nesta etapa. ' .
                'Utilize --diagnostico.'
            );

            return self::FAILURE;
        }

        try {
            $manager->connect();

            $this->info('Sessão SMPP estabelecida.');

            $this->line(
                'IP activo: ' . $manager->activeHost()
            );

            $this->line(
                'TPS configurado: ' .
                config('services.smpp.tps', 20)
            );

            $this->line(
                'Worker iniciado em modo diagnóstico.'
            );

            return self::SUCCESS;

        } catch (Throwable $e) {
            Log::error('SMPP_WORKER_ERRO', [
                'erro' => $e->getMessage(),
            ]);

            $this->error(
                'Falha SMPP: ' . $e->getMessage()
            );

            return self::FAILURE;

        } finally {
            $manager->disconnect();
        }
    }
}
