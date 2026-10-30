<?php

namespace App\Services\Dashboard;

use Illuminate\Support\Facades\DB;

class StockReconciliacaoService
{
    public function calcular(): array
    {
        $compras = DB::table('compras_stock_sms')
            ->where('operadora', 'MOVITEL')
            ->get();

        $resultado = [];

        foreach ($compras as $compra) {

            $consumido = (int) DB::table('sms_consumos_stock')
                ->where('compra_stock_sms_id', $compra->id)
                ->sum('segmentos');

            $esperado = (int) $compra->quantidade_sms - $consumido;

            $actual = (int) $compra->quantidade_disponivel
                + (int) $compra->quantidade_reservada;

            $diferenca = $actual - $esperado;

            $resultado[] = [
                'compra_id' => $compra->id,
                'referencia' => $compra->referencia,
                'quantidade_comprada' => (int) $compra->quantidade_sms,
                'quantidade_consumida' => $consumido,
                'stock_esperado' => $esperado,
                'stock_actual' => $actual,
                'diferenca' => $diferenca,
                'estado' => $diferenca === 0
                    ? 'CONCILIADO'
                    : 'DIVERGENTE',
            ];
        }

        return $resultado;
    }
}