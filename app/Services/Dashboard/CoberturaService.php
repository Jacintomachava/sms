<?php

namespace App\Services\Dashboard;

use Illuminate\Support\Facades\DB;

class CoberturaService
{
    public function calcular(int $stockFisico): array
    {
        /*
        |--------------------------------------------------------------------------
        | CRÉDITOS PRÉ-PAGOS LIVRES
        |--------------------------------------------------------------------------
        | Créditos já vendidos, mas ainda não reservados para envio.
        */
        $creditosPrePago = (int) DB::table('carteira_lotes_sms')
            ->where('estado', 'ACTIVO')
            ->sum('quantidade_disponivel');

        /*
        |--------------------------------------------------------------------------
        | RESERVAS ACTIVAS DO STOCK
        |--------------------------------------------------------------------------
        | Inclui reservas de envio pré-pago e pós-pago.
        */
        $reservasStock = (int) DB::table('sms_reservas')
            ->where('tipo', 'STOCK')
            ->where('estado', 'RESERVADA')
            ->sum('quantidade');

        /*
        |--------------------------------------------------------------------------
        | STOCK COMPROMETIDO
        |--------------------------------------------------------------------------
        */
        $stockComprometido = $creditosPrePago + $reservasStock;

        /*
        |--------------------------------------------------------------------------
        | STOCK DISPONÍVEL
        |--------------------------------------------------------------------------
        */
        $stockDisponivel = $stockFisico - $stockComprometido;

        /*
        |--------------------------------------------------------------------------
        | COBERTURA
        |--------------------------------------------------------------------------
        */
        $cobertura = $stockFisico > 0
            ? round(($stockDisponivel / $stockFisico) * 100, 2)
            : 0;

        /*
        |--------------------------------------------------------------------------
        | ESTADO DA COBERTURA
        |--------------------------------------------------------------------------
        */
        if ($stockFisico <= 0) {
            $estado = 'SEM_STOCK';
        } elseif ($stockDisponivel < 0) {
            $estado = 'DEFICE';
        } elseif ($cobertura < 40) {
            $estado = 'CRITICA';
        } elseif ($cobertura < 70) {
            $estado = 'ATENCAO';
        } else {
            $estado = 'SAUDAVEL';
        }

        return [
            'stock_fisico' => $stockFisico,
            'creditos_pre_pago' => $creditosPrePago,
            'reservas_stock' => $reservasStock,
            'stock_comprometido' => $stockComprometido,
            'stock_disponivel' => $stockDisponivel,
            'cobertura' => $cobertura,
            'estado' => $estado,
        ];
    }
}