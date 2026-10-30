<?php

namespace App\Services;

use App\Models\Conta;
use App\Services\SuspensaoFinanceiraSmsService;
use Illuminate\Support\Facades\DB;

class SuspensaoFinanceiraSmsService
{
    public function contarFacturasEmAtraso(int $contaId): int
    {
        return DB::table('facturas_sms as f')
            ->where('f.conta_id', $contaId)
            ->whereIn('f.estado', ['PENDENTE', 'VENCIDA'])
            ->whereDate('f.data_vencimento', '<', now('Africa/Maputo')->toDateString())
            ->whereRaw("
                (
                    f.valor_total + COALESCE(f.valor_multa, 0)
                ) > COALESCE((
                    SELECT SUM(fp.valor_aplicado)
                    FROM factura_pagamentos fp
                    WHERE fp.factura_sms_id = f.id
                ), 0)
            ")
            ->count();
    }

    /**
     * Chamado dentro de uma transacção que já bloqueou a conta.
     */
    public function verificarContaBloqueada(Conta $conta): void
    {
        if ($conta->tipo_cobranca !== 'POS_PAGO') {
            return;
        }

        if ($conta->estado === 'BLOQUEADA') {
            return;
        }

        $quantidade = $this->contarFacturasEmAtraso($conta->id);

        if ($quantidade >= 2) {

            if ($conta->estado === 'ACTIVA') {
                $conta->update([
                    'estado' => 'SUSPENSA',
                    'motivo_suspensao' => 'FACTURAS_EM_ATRASO',
                    'suspensa_em' => now(),
                ]);
            }

            return;
        }

        if (
            $conta->estado === 'SUSPENSA' &&
            $conta->motivo_suspensao === 'FACTURAS_EM_ATRASO'
        ) {
            $conta->update([
                'estado' => 'ACTIVA',
                'motivo_suspensao' => null,
                'suspensa_em' => null,
            ]);
        }
    }

    public function verificar(int $contaId): void
    {
        DB::transaction(function () use ($contaId) {

            $conta = Conta::query()
                ->whereKey($contaId)
                ->lockForUpdate()
                ->firstOrFail();

            $this->verificarContaBloqueada($conta);

        }, 3);
    }
}