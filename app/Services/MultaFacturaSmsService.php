<?php

namespace App\Services;

use App\Models\FacturaSms;
use Illuminate\Support\Facades\DB;

class MultaFacturaSmsService
{
    /**
     * Deve ser chamado com a factura bloqueada por lockForUpdate()
     * dentro de uma transacção.
     */
    public function aplicarEmFacturaBloqueada(FacturaSms $factura): bool
    {
        if ($factura->multa_aplicada_em !== null) {
            return false;
        }

        if (!in_array($factura->estado, ['PENDENTE', 'VENCIDA'], true)) {
            return false;
        }

        if (
            !$factura->data_vencimento ||
            $factura->data_vencimento->toDateString() >= now('Africa/Maputo')->toDateString()
        ) {
            return false;
        }

        /*
         * Calculamos os pagamentos realizados até ao vencimento.
         *
         * ATENÇÃO:
         * pago_em deve representar a data efectiva do pagamento.
         */
        $totalPagoAteVencimento = DB::table('factura_pagamentos as fp')
            ->join('pagamentos as p', 'p.id', '=', 'fp.pagamento_id')
            ->where('fp.factura_sms_id', $factura->id)
            ->where('p.pago_em', '<', $factura->data_vencimento->copy()->addDay())
            ->sum('fp.valor_aplicado');

        $saldoNoVencimento = bcsub(
            (string) $factura->valor_total,
            (string) $totalPagoAteVencimento,
            2
        );

        if (bccomp($saldoNoVencimento, '0', 2) <= 0) {
            return false;
        }

        /*
         * Multa fixa de 40%, aplicada uma única vez.
         */
        $multa = bcdiv(
            bcmul($saldoNoVencimento, '40', 2),
            '100',
            2
        );

        $factura->update([
            'percentagem_multa' => '40.00',
            'valor_multa' => $multa,
            'multa_aplicada_em' => now(),
        ]);

        return true;
    }

    public function aplicar(int $facturaId): bool
    {
        return DB::transaction(function () use ($facturaId) {

            $factura = FacturaSms::query()
                ->whereKey($facturaId)
                ->lockForUpdate()
                ->firstOrFail();

            return $this->aplicarEmFacturaBloqueada($factura);

        }, 3);
    }
}