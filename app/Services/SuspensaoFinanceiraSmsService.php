<?php

namespace App\Services;

use App\Models\Conta;
use Illuminate\Support\Facades\DB;

class SuspensaoFinanceiraSmsService
{
    public function contarFacturasEmAtraso(int $contaId): int
    {
        return DB::table('facturas_sms as f')
            ->where('f.conta_id', $contaId)
            ->whereIn('f.estado', ['PENDENTE', 'VENCIDA'])
            ->whereDate('f.data_vencimento', '<', now()->toDateString())
            ->whereRaw('
                (
                    f.valor_total + COALESCE(f.valor_multa, 0)
                ) > COALESCE((
                    SELECT SUM(fp.valor_aplicado)
                    FROM factura_pagamentos fp
                    WHERE fp.factura_sms_id = f.id
                ), 0)
            ')
            ->count();
    }

    public function verificar(int $contaId): void
    {
        DB::transaction(function () use ($contaId) {

            $conta = Conta::whereKey($contaId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($conta->modo_cobranca !== 'POS_PAGO') {
                return;
            }

            // Nunca alterar contas bloqueadas administrativamente.
            if ($conta->estado === 'BLOQUEADA') {
                return;
            }

            $facturasEmAtraso = $this->contarFacturasEmAtraso($contaId);

            if ($facturasEmAtraso >= 2) {

                // Não substituir uma suspensão administrativa.
                if ($conta->estado === 'ACTIVA') {

                    $conta->update([
                        'estado' => 'SUSPENSA',
                        'motivo_suspensao' => 'FACTURAS_EM_ATRASO',
                        'suspensa_em' => now(),
                    ]);
                }

                return;
            }

            // Reactivar apenas suspensões financeiras automáticas.
            if ($conta->estado === 'SUSPENSA' && $conta->motivo_suspensao === 'FACTURAS_EM_ATRASO') {
                
                $conta->update([
                    'estado' => 'ACTIVA',
                    'motivo_suspensao' => null,
                    'suspensa_em' => null,
                ]);
            }
        }, 3);
    }
}