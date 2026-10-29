<?php

namespace App\Console\Commands;

use App\Models\CicloConsumoSms;
use App\Models\FacturaSms;
use App\Services\FacturacaoSmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class FacturarCiclosSms extends Command
{
    protected $signature = 'sms:facturar-ciclos
                            {--ciclo= : ID de um ciclo específico}
                            {--dry-run : Simular sem efectuar alterações}';

    protected $description = 'Fecha ciclos pós-pagos anteriores e emite as respectivas facturas.';

    public function handle(FacturacaoSmsService $facturacao): int
    {
        $this->info('======================================');
        $this->info('FACTURAÇÃO AUTOMÁTICA INFOSMS');
        $this->info('======================================');

        $hoje = now()->toDateString();

        $query = CicloConsumoSms::query()
            ->where('periodo_fim', '<', $hoje)
            ->whereIn('estado', ['ABERTO', 'FECHADO']);

        if ($this->option('ciclo')) {
            $query->whereKey((int) $this->option('ciclo'));
        }

        $processados = 0;
        $facturados = 0;
        $pendentes = 0;
        $erros = 0;

        $query->orderBy('id')
            ->chunkById(100, function ($ciclos) use (
                $facturacao,
                &$processados,
                &$facturados,
                &$pendentes,
                &$erros
            ) {
                foreach ($ciclos as $ciclo) {
                    $processados++;

                    try {
                        /*
                        |--------------------------------------------------------------------------
                        | VERIFICAR RESERVAS
                        |--------------------------------------------------------------------------
                        */

                        if (
                            $ciclo->estado === 'ABERTO' &&
                            (int) $ciclo->segmentos_reservados > 0
                        ) {
                            $pendentes++;

                            $this->warn(
                                "Ciclo {$ciclo->id}: "
                                . "{$ciclo->segmentos_reservados} "
                                . "segmentos pendentes."
                            );

                            continue;
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | SIMULAÇÃO
                        |--------------------------------------------------------------------------
                        */
                        if ($this->option('dry-run')) {
                            $this->line(
                                "Ciclo {$ciclo->id}: "
                                . "elegível para facturação."
                            );

                            continue;
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | FECHAR CICLO
                        |--------------------------------------------------------------------------
                        */
                        if ($ciclo->estado === 'ABERTO') {
                            $facturacao->fecharCiclo($ciclo->id);
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | VERIFICAR FACTURA EXISTENTE
                        |--------------------------------------------------------------------------
                        */
                        $facturaExistente = FacturaSms::query()
                            ->where(
                                'ciclo_consumo_sms_id',
                                $ciclo->id
                            )
                            ->first();

                        if ($facturaExistente) {
                            $this->warn(
                                "Ciclo {$ciclo->id}: "
                                . "já possui factura "
                                . "{$facturaExistente->numero}."
                            );

                            continue;
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | GERAR FACTURA
                        |--------------------------------------------------------------------------
                        */
                        $factura = $facturacao->gerarFactura(
                            $ciclo->id
                        );

                        $facturados++;

                        $this->info(
                            "Factura {$factura->numero} criada. "
                            . "Valor: {$factura->valor_total} MZN."
                        );

                    } catch (Throwable $e) {
                        $erros++;

                        Log::error(
                            'Erro na facturação automática de SMS',
                            [
                                'ciclo_id' => $ciclo->id,
                                'erro' => $e->getMessage(),
                            ]
                        );

                        $this->error(
                            "Ciclo {$ciclo->id}: "
                            . $e->getMessage()
                        );
                    }
                }
            });

        $this->newLine();

        $this->table(
            ['Indicador', 'Quantidade'],
            [
                ['Ciclos analisados', $processados],
                ['Facturas emitidas', $facturados],
                ['Ciclos com reservas pendentes', $pendentes],
                ['Erros', $erros],
            ]
        );

        return $erros > 0
            ? self::FAILURE
            : self::SUCCESS;
    }
}