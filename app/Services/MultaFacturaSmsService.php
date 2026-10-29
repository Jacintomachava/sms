<?php

namespace App\Services;

use App\Models\FacturaSms;
use Illuminate\Support\Facades\DB;

class MultaFacturaSmsService
{
    public function aplicar(int $facturaId): bool
    {
        return DB::transaction(function () use ($facturaId) {

            $factura = FacturaSms::whereKey($facturaId)
                ->lockForUpdate()
                ->firstOrFail();

            // Não aplicar multa duas vezes.
            if ($factura->multa_aplicada_em !== null) {
                return false;
            }

            // Não aplicar em facturas pagas ou canceladas.
            if (!in_array($factura->estado, ['PENDENTE', 'VENCIDA'])) {
                return false;
            }

            // O prazo deve estar ultrapassado.
            if (
                !$factura->data_vencimento ||
                $factura->data_vencimento >= now('Africa/Maputo')->toDateString()
            ) {
                return false;
            }

            $totalPago = DB::table('factura_pagamentos')
                ->where('factura_sms_id', $factura->id)
                ->sum('valor_aplicado');

            $saldo = bcsub(
                (string) $factura->valor_total,
                (string) $totalPago,
                2
            );

            if (bccomp($saldo, '0', 2) <= 0) {
                return false;
            }

            $multa = bcmul($saldo, '0.40', 3);

            // Arredondamento comercial para duas casas decimais.
            $multa = bcadd($multa, '0.005', 2);

            $factura->update([
                'percentagem_multa' => 40,
                'valor_multa' => $multa,
                'multa_aplicada_em' => now(),
            ]);

            return true;

        }, 3);
    }
}