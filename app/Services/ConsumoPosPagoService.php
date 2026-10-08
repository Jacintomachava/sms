<?php

namespace App\Services;

use App\Models\CicloConsumoSms;
use App\Models\Conta;
use App\Models\TarifaSms;
use RuntimeException;

class ConsumoPosPagoService
{
    public function registar(Conta $conta, TarifaSms $tarifa, int $segmentos): CicloConsumoSms {

        if ($segmentos < 1) {
            throw new RuntimeException(
                'Quantidade de segmentos inválida.'
            );
        }

        $inicio = now()
            ->startOfMonth()
            ->toDateString();

        $fim = now()
            ->endOfMonth()
            ->toDateString();

        /*
        |--------------------------------------------------------------------------
        | PROCURAR CICLO DO MÊS
        |--------------------------------------------------------------------------
        |
        | A transacção principal pertence ao SmsService.
        |
        */
        $ciclo = CicloConsumoSms::query()
            ->where('conta_id', $conta->id)
            ->where('periodo_inicio', $inicio)
            ->where('periodo_fim', $fim)
            ->lockForUpdate()
            ->first();

        /*
        |--------------------------------------------------------------------------
        | ABRIR NOVO CICLO
        |--------------------------------------------------------------------------
        */
        if (!$ciclo) {

            $ciclo = CicloConsumoSms::create([

                'conta_id' => $conta->id,

                /*
                | Snapshot da tarifa.
                */
                'tarifa_sms_id' => $tarifa->id,

                'periodo_inicio' => $inicio,
                'periodo_fim' => $fim,

                'quantidade_sms' => 0,
                'segmentos' => 0,

                /*
                | Snapshot do preço.
                */
                'preco_unitario' => $tarifa->preco_sms,

                'valor_estimado' => 0,

                'moeda' => 'MZN',

                'estado' => 'ABERTO',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | CICLO PRECISA ESTAR ABERTO
        |--------------------------------------------------------------------------
        */
        if ($ciclo->estado !== 'ABERTO') {

            throw new RuntimeException(
                'O ciclo de consumo encontra-se fechado.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | CALCULAR VALOR
        |--------------------------------------------------------------------------
        */
        $valor = bcmul(
            (string) $segmentos,
            (string) $ciclo->preco_unitario,
            4
        );

        /*
        |--------------------------------------------------------------------------
        | ACTUALIZAR CONSUMO
        |--------------------------------------------------------------------------
        */
        $ciclo->increment(
            'quantidade_sms',
            1
        );

        $ciclo->increment(
            'segmentos',
            $segmentos
        );

        $ciclo->increment(
            'valor_estimado',
            $valor
        );

        return $ciclo->fresh();
    }
}