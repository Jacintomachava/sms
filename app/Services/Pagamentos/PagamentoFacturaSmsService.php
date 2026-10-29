<?php

namespace App\Services\Pagamentos;

use App\Models\Conta;
use App\Models\FacturaSms;
use App\Models\Pagamento;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class PagamentoFacturaSmsService
{
    /**
     * Regista um pagamento confirmado e distribui
     * o valor por uma ou várias facturas.
     *
     * $facturas:
     * [
     *     ['factura_id' => 1, 'valor' => '2000.00'],
     *     ['factura_id' => 2, 'valor' => '1000.00'],
     * ]
     */
    public function registar(
        int $contaId,
        string $formaPagamento,
        array $facturas,
        ?string $referencia = null,
        ?int $userId = null
    ): Pagamento {

        return DB::transaction(function () use (
            $contaId,
            $formaPagamento,
            $facturas,
            $referencia,
            $userId
        ) {

            /*
            |--------------------------------------------------------------------------
            | VALIDAR FORMA DE PAGAMENTO
            |--------------------------------------------------------------------------
            */
            $formasPermitidas = [
                'MPESA',
                'CASH',
                'DEPOSITO',
                'TRANSFERENCIA',
            ];

            if (!in_array($formaPagamento, $formasPermitidas, true)) {
                throw new RuntimeException(
                    'Forma de pagamento inválida.'
                );
            }

            if (empty($facturas)) {
                throw new RuntimeException(
                    'Nenhuma factura foi informada.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | BLOQUEAR CONTA
            |--------------------------------------------------------------------------
            */
            $conta = Conta::query()
                ->whereKey($contaId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($conta->tipo_cobranca !== 'POS_PAGO') {
                throw new RuntimeException(
                    'A conta não utiliza cobrança pós-paga.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | VALIDAR FACTURAS INFORMADAS
            |--------------------------------------------------------------------------
            */
            $valores = [];

            foreach ($facturas as $item) {

                $facturaId = (int) ($item['factura_id'] ?? 0);
                $valor = $item['valor'] ?? null;

                if ($facturaId <= 0) {
                    throw new RuntimeException(
                        'Identificador de factura inválido.'
                    );
                }

                if (isset($valores[$facturaId])) {
                    throw new RuntimeException(
                        "Factura {$facturaId} informada duas vezes."
                    );
                }

                if (
                    !is_string($valor) ||
                    !preg_match('/^\d+(\.\d{1,2})?$/', $valor) ||
                    bccomp($valor, '0', 2) <= 0
                ) {
                    throw new RuntimeException(
                        "Valor inválido para a factura {$facturaId}."
                    );
                }

                $valores[$facturaId] = bcadd($valor, '0', 2);
            }

            /*
            |--------------------------------------------------------------------------
            | BLOQUEAR FACTURAS EM ORDEM FIXA
            |--------------------------------------------------------------------------
            */
            ksort($valores, SORT_NUMERIC);

            $facturasBloqueadas = FacturaSms::query()
                ->whereIn('id', array_keys($valores))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($facturasBloqueadas->count() !== count($valores)) {
                throw new RuntimeException(
                    'Uma ou mais facturas não foram encontradas.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | VALIDAR SALDOS E CALCULAR TOTAL
            |--------------------------------------------------------------------------
            */
            $totalPagamento = '0.00';
            $moeda = null;

            foreach ($valores as $facturaId => $valorAplicado) {

                $factura = $facturasBloqueadas[$facturaId];

                if ((int) $factura->conta_id !== $contaId) {
                    throw new RuntimeException(
                        'A factura não pertence à conta informada.'
                    );
                }

                if (!in_array(
                    $factura->estado,
                    ['PENDENTE', 'VENCIDA'],
                    true
                )) {
                    throw new RuntimeException(
                        "A factura {$factura->numero} não está disponível para pagamento."
                    );
                }

                if ($moeda === null) {
                    $moeda = $factura->moeda;
                } elseif ($moeda !== $factura->moeda) {
                    throw new RuntimeException(
                        'Não é permitido misturar moedas no mesmo pagamento.'
                    );
                }

                $totalPago = DB::table('factura_pagamentos')
                    ->where('factura_sms_id', $facturaId)
                    ->sum('valor_aplicado');

                $saldo = bcsub(
                    (string) $factura->valor_total,
                    (string) $totalPago,
                    2
                );

                if (bccomp($valorAplicado, $saldo, 2) > 0) {
                    throw new RuntimeException(
                        "O valor informado ultrapassa o saldo da factura {$factura->numero}."
                    );
                }

                $totalPagamento = bcadd(
                    $totalPagamento,
                    $valorAplicado,
                    2
                );
            }

            /*
            |--------------------------------------------------------------------------
            | REGISTAR PAGAMENTO
            |--------------------------------------------------------------------------
            |
            | Este método deve ser chamado apenas depois
            | da confirmação efectiva do recebimento.
            |
            */
            $pagamento = Pagamento::create([
                'conta_id' => $contaId,
                'compra_sms_id' => null,
                'transacao_pagamento_id' => null,
                'tipo' => 'FACTURA',
                'forma_pagamento' => $formaPagamento,
                'valor' => $totalPagamento,
                'moeda' => $moeda,
                'referencia' => $referencia,
                'pago_em' => now(),
                'confirmado_por' => $userId,
            ]);

            /*
            |--------------------------------------------------------------------------
            | DISTRIBUIR PAGAMENTO
            |--------------------------------------------------------------------------
            */
            foreach ($valores as $facturaId => $valorAplicado) {

                $factura = $facturasBloqueadas[$facturaId];

                $pagamento->facturas()->attach($facturaId, [
                    'valor_aplicado' => $valorAplicado,
                ]);

                $totalPago = DB::table('factura_pagamentos')
                    ->where('factura_sms_id', $facturaId)
                    ->sum('valor_aplicado');

                if (
                    bccomp(
                        (string) $totalPago,
                        (string) $factura->valor_total,
                        2
                    ) === 0
                ) {
                    $factura->update([
                        'estado' => 'PAGA',
                        'paga_em' => now(),
                    ]);
                }
            }

            return $pagamento->fresh();
        }, 3);
    }
}