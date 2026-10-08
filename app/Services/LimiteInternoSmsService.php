<?php

namespace App\Services;

use App\Models\Conta;
use App\Models\ContaLimiteInternoSms;
use App\Models\CicloConsumoSms;
use RuntimeException;

class LimiteInternoSmsService
{
    public function validarEnvio(Conta $conta, int $novosSegmentos ): void {

        if ($conta->tipo_cobranca !== 'POS_PAGO') {
            return;
        }

        $limite = ContaLimiteInternoSms::query()
                ->where('conta_id', $conta->id)
                ->where('periodo','MENSAL')
                ->where('activo', true)
                ->first();

        /*
         * Isto idealmente nunca deve acontecer
         * numa conta POS_PAGO.
         *
         * Segurança: sem limite interno,
         * não permitimos envio.
         */
        if (!$limite) {

            throw new RuntimeException(
                'Envio de SMS indisponível para esta conta.'
            );
        }

        $inicio = now() ->startOfMonth() ->toDateString();
        $fim = now() ->endOfMonth() ->toDateString();

        $ciclo =
            CicloConsumoSms::query()
                ->where('conta_id', $conta->id)
                ->where('periodo_inicio', $inicio)
                ->where('periodo_fim', $fim)
                ->first();

        $consumoActual = $ciclo?->segmentos ?? 0;
        $consumoDepois = $consumoActual + $novosSegmentos;

        if ($consumoDepois > $limite->limite_sms) {

            throw new RuntimeException(
                'Envio de SMS indisponível para esta conta.'
            );
        }
    }
}