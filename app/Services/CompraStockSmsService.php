<?php

namespace App\Services;

use App\Models\CompraStockSms;
use App\Models\StockSmsMovimento;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CompraStockSmsService
{
    public function criar(int $quantidade, string $precoUnitario, string $dataCompra, ?string $referencia = null, ?string $observacao = null, ?int $userId = null, string $operadora = 'MOVITEL' ): CompraStockSms {

        if ($quantidade <= 0) {
            throw new RuntimeException(
                'A quantidade de SMS deve ser superior a zero.'
            );
        }

        if ((float) $precoUnitario <= 0) {
            throw new RuntimeException(
                'O preço unitário deve ser superior a zero.'
            );
        }

        $operadora = strtoupper(trim($operadora));

        /*
         * Por enquanto o valor vem da multiplicação.
         * Mais tarde podemos padronizar toda a matemática
         * financeira com BCMath.
         */
        $valorTotal = number_format($quantidade * (float) $precoUnitario, 2,'.','');

        return DB::transaction(function () use (
            $quantidade,
            $precoUnitario,
            $dataCompra,
            $referencia,
            $observacao,
            $userId,
            $operadora,
            $valorTotal
        ) {

            $compra = CompraStockSms::create([
                'operadora' => $operadora,
                'quantidade_sms' => $quantidade,
                'quantidade_disponivel' => $quantidade,
                'preco_unitario' => $precoUnitario,
                'valor_total' => $valorTotal,
                'moeda' => 'MZN',
                'iva_incluido' => true,
                'referencia' => $referencia,
                'data_compra' => $dataCompra,
                'observacao' => $observacao,
                'estado' => 'ACTIVO',
                'criado_por' => $userId,
            ]);

            StockSmsMovimento::create([
                'compra_stock_sms_id' => $compra->id,
                'tipo' => 'ENTRADA',
                'quantidade_sms' => $quantidade,
                'saldo_anterior' => 0,
                'saldo_posterior' => $quantidade,
                'referencia' => 'COMPRA-' . $compra->id,
                'descricao' => 'Entrada de stock por compra à ' . $operadora,

                'metadata' => [
                    'referencia_fornecedor' => $referencia,
                    'preco_unitario' => $precoUnitario,
                    'valor_total' => $valorTotal,
                ],

                'user_id' => $userId,
            ]);

            return $compra;
        });
    }
}