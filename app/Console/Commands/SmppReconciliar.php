<?php

namespace App\Console\Commands;

use App\Services\SMPP\SmppReconciliacaoService;
use Illuminate\Console\Command;

class SmppReconciliar extends Command
{
    protected $signature = 'smpp:reconciliar {--limite=100}';

    protected $description = 'Executa a reconciliação automática de SMS incertas.';

    public function handle(SmppReconciliacaoService $service): int {

        $limite = (int) $this->option('limite');

        if ($limite < 1 || $limite > 500) {
            $this->error('O limite deve estar entre 1 e 500.');

            return self::FAILURE;
        }

        $resultado = $service->executar($limite);

        $this->line(json_encode(
            $resultado,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        ));

        return empty($resultado['erros'])
            ? self::SUCCESS
            : self::FAILURE;
    }
}