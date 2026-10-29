<?php

namespace App\Services;

use App\Models\Conta;
use Illuminate\Support\Facades\DB;

class BloqueioFinanceiroSmsService
{
    public function quantidadeFacturasEmAtraso(int $contaId): int
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

    public function estaBloqueada(int $contaId): bool
    {
        $conta = Conta::findOrFail($contaId);

        if ($conta->modo_cobranca !== 'POS_PAGO') {
            return false;
        }

        return $this->quantidadeFacturasEmAtraso($contaId) >= 2;
    }

    public function validarEnvio(int $contaId): void
    {
        if ($this->estaBloqueada($contaId)) {
            throw new \RuntimeException(
                'Envio de SMS bloqueado: a conta possui duas ou mais facturas em atraso. Regularize os pagamentos para continuar.'
            );
        }
    }
}