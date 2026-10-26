<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\SMPP\SmppRecuperacaoService;
use Illuminate\Console\Command;

class SmppDiagnosticarPreparadas extends Command
{
    protected $signature = 'smpp:diagnosticar-preparadas
                            {--limite=100 : Número máximo de tentativas}';

    protected $description =
        'Diagnostica tentativas SMPP preparadas sem iniciar transmissão.';

    public function handle(
        SmppRecuperacaoService $recuperacao
    ): int {

        $limite = (int) $this->option('limite');

        if ($limite < 1 || $limite > 1000) {
            $this->error('O limite deve estar entre 1 e 1000.');

            return self::FAILURE;
        }

        $resultado = $recuperacao->diagnosticarPreparadas(
            $limite
        );

        $this->info(
            'Tentativas analisadas: ' . $resultado['analisadas']
        );

        $this->info(
            'Candidatas: ' . count($resultado['candidatas'])
        );

        $this->warn(
            'Bloqueadas: ' . count($resultado['bloqueadas'])
        );

        foreach ($resultado['candidatas'] as $tentativa) {

            $this->line(sprintf(
                'SMS %d | Tentativa %d | Segmentos %d | %s',
                $tentativa['sms_id'],
                $tentativa['tentativa_id'],
                $tentativa['segmentos'],
                $tentativa['motivo']
            ));
        }

        foreach ($resultado['bloqueadas'] as $tentativa) {

            $this->warn(sprintf(
                'SMS %d | Tentativa %d | %s',
                $tentativa['sms_id'],
                $tentativa['tentativa_id'],
                $tentativa['motivo']
            ));
        }

        return self::SUCCESS;
    }
}