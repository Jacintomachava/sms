<?php

namespace App\Services;

use App\Models\Conta;
use App\Models\ContaLimiteSms;
use App\Models\CicloConsumoSms;
use RuntimeException;

class LimiteSmsService
{
    public function validarEnvio(Conta $conta, int $novosSegmentos): void {

        /*
        |--------------------------------------------------------------------------
        | APENAS POS-PAGO
        |--------------------------------------------------------------------------
        */
        if ($conta->tipo_cobranca !== 'POS_PAGO') {
            return;
        }

        if ($novosSegmentos < 1) {
            throw new RuntimeException(
                'Quantidade de segmentos inválida.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | LIMITE DEFINIDO PELO CLIENTE
        |--------------------------------------------------------------------------
        |
        | Diferente do limite interno INFORDATA:
        |
        | - este limite é opcional;
        | - se não existir, o envio continua;
        | - se estiver inactivo, o envio continua;
        | - só bloqueia quando bloqueio_activo = true.
        |
        */
        $limite = ContaLimiteSms::query()
            ->where('conta_id', $conta->id)
            ->where('periodo', 'MENSAL')
            ->where('activo', true)
            ->first();

        if (!$limite) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | BLOQUEIO
        |--------------------------------------------------------------------------
        |
        | O cliente pode querer apenas alertas,
        | sem bloquear os envios.
        |
        */
        if (!$limite->bloqueio_activo) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | CONSUMO ACTUAL
        |--------------------------------------------------------------------------
        */
        $inicio = now() ->startOfMonth() ->toDateString();
        $fim = now() ->endOfMonth() ->toDateString();

        $ciclo = CicloConsumoSms::query()
            ->where('conta_id', $conta->id)
            ->where('periodo_inicio', $inicio)
            ->where('periodo_fim', $fim)
            ->first();

        $consumoActual = (int) ($ciclo?->segmentos ?? 0);
        $consumoDepois = $consumoActual + $novosSegmentos;

        /*
        |--------------------------------------------------------------------------
        | LIMITE DE CORTE
        |--------------------------------------------------------------------------
        |
        | limite_sms       = 10.000
        | corte_percentual = 110
        |
        | corte efectivo:
        |
        | 10.000 × 110 / 100 = 11.000 segmentos
        |
        | Se corte_percentual não estiver definido,
        | usamos 100% do limite.
        |
        */
        $percentualCorte = $limite->corte_percentual ?: 100;
        $limiteCorte = (int) floor( ($limite->limite_sms * $percentualCorte) / 100);

        /*
        |--------------------------------------------------------------------------
        | VALIDAR
        |--------------------------------------------------------------------------
        */
        if ($consumoDepois > $limiteCorte) {

            throw new RuntimeException(
                'Limite de SMS definido para a conta foi atingido.'
            );
        }
    }
}