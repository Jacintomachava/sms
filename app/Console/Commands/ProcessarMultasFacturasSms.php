<?php

namespace App\Console\Commands;

use App\Models\FacturaSms;
use App\Services\MultaFacturaSmsService;
use App\Services\SuspensaoFinanceiraSmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessarMultasFacturasSms extends Command
{
    protected $signature = 'sms:processar-multas {--dry-run}';

    protected $description = 'Aplica multas e actualiza suspensões financeiras';

    public function handle(): int
    {
        $hoje = now('Africa/Maputo')->toDateString();

        $this->info("Processamento de multas SMS: {$hoje}");

        $facturas = FacturaSms::query()
            ->whereIn('estado', ['PENDENTE', 'VENCIDA'])
            ->whereDate('data_vencimento', '<', $hoje)
            ->whereNull('multa_aplicada_em')
            ->orderBy('id')
            ->get();

        $aplicadas = 0;
        $erros = 0;
        $contas = [];

        foreach ($facturas as $factura) {

            $contas[$factura->conta_id] = true;

            if ($this->option('dry-run')) {
                $this->line("[SIMULAÇÃO] {$factura->numero}");
                continue;
            }

            try {

                if (app(MultaFacturaSmsService::class)->aplicar($factura->id)) {
                    $aplicadas++;
                }

            } catch (Throwable $e) {

                $erros++;

                report($e);

                Log::error('Erro ao aplicar multa SMS', [
                    'factura_id' => $factura->id,
                    'erro' => $e->getMessage(),
                ]);
            }
        }

        if (!$this->option('dry-run')) {

            /*
             * Verificar todas as contas pós-pagas, inclusive aquelas
             * cujas facturas já tiveram multas aplicadas.
             */
            $contasPosPago = \App\Models\Conta::query()
                ->where('tipo_cobranca', 'POS_PAGO')
                ->pluck('id');

            foreach ($contasPosPago as $contaId) {

                try {

                    app(SuspensaoFinanceiraSmsService::class)
                        ->verificar((int) $contaId);

                } catch (Throwable $e) {

                    $erros++;
                    report($e);
                }
            }
        }

        $this->info("Multas aplicadas: {$aplicadas}");
        $this->info("Erros: {$erros}");

        return $erros === 0 ? self::SUCCESS : self::FAILURE;
    }
}