<?php

namespace App\Services\Pagamentos;

use App\Models\Conta;
use App\Models\FacturaSms;
use App\Models\Pagamento;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PagamentoFacturaSmsService
{
    public function registar(
        int $contaId,
        string $formaPagamento,
        array $facturas,
        ?string $referencia = null,
        ?int $userId = null,
        ?string $chaveIdempotencia = null
    ): Pagamento {

        /*
        |--------------------------------------------------------------------------
        | VALIDAR CHAVE DE IDEMPOTÊNCIA
        |--------------------------------------------------------------------------
        */

        if (
            !is_string($chaveIdempotencia) ||
            trim($chaveIdempotencia) === '' ||
            strlen($chaveIdempotencia) > 100
        ) {
            throw new RuntimeException(
                'Chave de idempotência obrigatória ou inválida.'
            );
        }

        $formasPermitidas = [
            'MPESA',
            'CASH',
            'DEPOSITO',
            'TRANSFERENCIA',
        ];

        if (!in_array($formaPagamento, $formasPermitidas, true)) {
            throw new RuntimeException('Forma de pagamento inválida.');
        }

        if (empty($facturas)) {
            throw new RuntimeException('Nenhuma factura informada.');
        }

        /*
        |--------------------------------------------------------------------------
        | NORMALIZAR DISTRIBUIÇÃO
        |--------------------------------------------------------------------------
        */

        $valores = [];

        foreach ($facturas as $item) {

            $facturaId = (int) ($item['factura_id'] ?? 0);
            $valor = $item['valor'] ?? null;

            if ($facturaId <= 0) {
                throw new RuntimeException('Factura inválida.');
            }

            if (isset($valores[$facturaId])) {
                throw new RuntimeException(
                    "Factura {$facturaId} duplicada na operação."
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

        ksort($valores, SORT_NUMERIC);

        /*
        |--------------------------------------------------------------------------
        | TRANSAÇÃO
        |--------------------------------------------------------------------------
        */

        return DB::transaction(function () use (
            $contaId,
            $formaPagamento,
            $valores,
            $referencia,
            $userId,
            $chaveIdempotencia
        ) {

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
            | VERIFICAR PAGAMENTO EXISTENTE
            |--------------------------------------------------------------------------
            */

            $existente = Pagamento::query()
                ->where('chave_idempotencia', $chaveIdempotencia)
                ->lockForUpdate()
                ->first();

            if ($existente) {

                if (
                    (int) $existente->conta_id !== $contaId ||
                    $existente->tipo !== 'FACTURA' ||
                    $existente->forma_pagamento !== $formaPagamento ||
                    $existente->referencia !== $referencia
                ) {
                    throw new RuntimeException(
                        'Chave de idempotência utilizada noutra operação.'
                    );
                }

                $distribuicaoOriginal = DB::table('factura_pagamentos')
                    ->where('pagamento_id', $existente->id)
                    ->orderBy('factura_sms_id')
                    ->pluck('valor_aplicado', 'factura_sms_id')
                    ->map(fn ($valor) => bcadd((string) $valor, '0', 2))
                    ->all();

                if ($distribuicaoOriginal != $valores) {
                    throw new RuntimeException(
                        'A distribuição não corresponde à operação original.'
                    );
                }

                return $existente;
            }

            /*
            |--------------------------------------------------------------------------
            | BLOQUEAR FACTURAS
            |--------------------------------------------------------------------------
            */

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

            $totalPagamento = '0.00';
            $moeda = null;

            /*
            |--------------------------------------------------------------------------
            | VALIDAR SALDOS
            |--------------------------------------------------------------------------
            */

            foreach ($valores as $facturaId => $valorAplicado) {

                $factura = $facturasBloqueadas[$facturaId];

                if ((int) $factura->conta_id !== $contaId) {
                    throw new RuntimeException(
                        'Factura não pertence à conta.'
                    );
                }

                if (!in_array(
                    $factura->estado,
                    ['PENDENTE', 'VENCIDA'],
                    true
                )) {
                    throw new RuntimeException(
                        "Factura {$factura->numero} indisponível para pagamento."
                    );
                }

                if ($moeda === null) {
                    $moeda = $factura->moeda;
                } elseif ($moeda !== $factura->moeda) {
                    throw new RuntimeException(
                        'Não é permitido misturar moedas.'
                    );
                }

                $totalPago = DB::table('factura_pagamentos')
                    ->where('factura_sms_id', $facturaId)
                    ->sum('valor_aplicado');

                $totalComMulta = bcadd((string) $factura->valor_total, (string) ($factura->valor_multa ?? '0'), 2);
                $saldo = bcsub($totalComMulta, (string) $totalPago, 2);

                if (bccomp($valorAplicado, $saldo, 2) > 0) {
                    throw new RuntimeException(
                        "Valor superior ao saldo da factura {$factura->numero}."
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
            | CRIAR PAGAMENTO
            |--------------------------------------------------------------------------
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
                'chave_idempotencia' => $chaveIdempotencia,
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

                if (bccomp((string) $totalPago, (string) $factura->valor_total, 2) === 0) {
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