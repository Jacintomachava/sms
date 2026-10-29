<?php

namespace App\Console\Commands;

use App\Models\Conta;
use App\Models\FacturaSms;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CriarFacturasTesteSms extends Command
{
    protected $signature = 'sms:criar-facturas-teste';

    protected $description = 'Cria duas facturas de teste para validar pagamentos pós-pagos';

    public function handle(): int
    {
        $conta = Conta::find(6);

        if (!$conta || $conta->tipo_cobranca !== 'POS_PAGO') {
            $this->error('Conta pós-paga não encontrada.');
            return self::FAILURE;
        }

        $dados = [
            [
                'numero' => 'TESTE-FT-001',
                'quantidade_sms' => 2000,
                'segmentos' => 2400,
                'valor' => '3000.00',
            ],
            [
                'numero' => 'TESTE-FT-002',
                'quantidade_sms' => 1200,
                'segmentos' => 1600,
                'valor' => '2000.00',
            ],
        ];

        DB::transaction(function () use ($conta, $dados) {

            foreach ($dados as $item) {

                if (FacturaSms::where('numero', $item['numero'])->exists()) {
                    $this->warn("Factura {$item['numero']} já existe.");
                    continue;
                }

                $factura = FacturaSms::create([
                    'conta_id' => $conta->id,
                    'ciclo_consumo_sms_id' => null,
                    'numero' => $item['numero'],
                    'periodo_inicio' => '2026-09-01',
                    'periodo_fim' => '2026-09-30',
                    'quantidade_sms' => $item['quantidade_sms'],
                    'segmentos' => $item['segmentos'],
                    'preco_unitario' => '1.2500',
                    'subtotal' => $item['valor'],
                    'valor_total' => $item['valor'],
                    'moeda' => 'MZN',
                    'data_emissao' => '2026-10-01',
                    'data_vencimento' => '2026-10-15',
                    'estado' => 'PENDENTE',
                    'observacao' => 'DOCUMENTO DE TESTE - SEM VALIDADE FISCAL',
                ]);

                $this->info(
                    "Factura {$factura->numero} criada. ID: {$factura->id}"
                );
            }
        });

        return self::SUCCESS;
    }
}