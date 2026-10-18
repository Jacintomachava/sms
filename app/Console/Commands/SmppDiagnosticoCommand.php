<?php

namespace App\Console\Commands;

use App\Services\SMPP\SmppConnectionManager;
use Illuminate\Console\Command;
use Throwable;

class SmppDiagnosticoCommand extends Command
{
    protected $signature = 'smpp:diagnostico';

    protected $description =
        'Verifica a ligação SMPP à Movitel sem enviar SMS';

    public function handle(
        SmppConnectionManager $manager
    ): int {
        $this->info('INFORDATA SMS - Diagnóstico SMPP');
        $this->line('-------------------------------');

        try {
            $manager->connect();

            $this->info(
                'Ligação estabelecida com sucesso.'
            );

            $this->line(
                'IP activo: ' . $manager->activeHost()
            );

            $this->line(
                'TPS configurado: ' .
                config('services.smpp.tps', 20)
            );

            return self::SUCCESS;

        } catch (Throwable $e) {
            $this->error(
                'Falha de ligação SMPP: ' .
                $e->getMessage()
            );

            return self::FAILURE;

        } finally {
            $manager->disconnect();
        }
    }
}
