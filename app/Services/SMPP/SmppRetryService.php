<?php

declare(strict_types=1);

namespace App\Services\SMPP;

use Illuminate\Support\Facades\DB;

class SmppRetryService
{
    private const MAX_TENTATIVAS = 5;

    private const INTERVALO_MINUTOS = 5;

    /**
     * Avalia se uma SMS pode ser considerada
     * para uma nova tentativa.
     *
     * Não altera estados, não envia PDUs
     * e não movimenta reservas.
     */
    public function avaliar(int $smsId): array
    {
        $sms = DB::table('sms')
            ->where('id', $smsId)
            ->first();

        if (!$sms) {
            return $this->resultado(
                false,
                'SMS_NAO_ENCONTRADA'
            );
        }

        if ($sms->estado !== 'PENDENTE') {
            return $this->resultado(
                false,
                'SMS_NAO_PENDENTE'
            );
        }

        if (
            $sms->proxima_tentativa_em !== null &&
            $sms->proxima_tentativa_em > now()->toDateTimeString()
        ) {
            return $this->resultado(
                false,
                'AGUARDAR_INTERVALO'
            );
        }

        $tentativas = DB::table('smpp_tentativas_envio')
            ->where('sms_id', $smsId)
            ->orderBy('id')
            ->get();

        if ($tentativas->count() >= self::MAX_TENTATIVAS) {
            return $this->resultado(
                false,
                'LIMITE_TENTATIVAS'
            );
        }

        if ($tentativas->isEmpty()) {
            return $this->resultado(
                true,
                'PRIMEIRA_TENTATIVA'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDAR TENTATIVAS ANTERIORES
        |--------------------------------------------------------------------------
        |
        | Só permitimos uma nova tentativa quando todas
        | as anteriores foram canceladas sem transmissão.
        |
        */
        foreach ($tentativas as $tentativa) {

            if ($tentativa->estado !== 'CANCELADA_SEM_ENVIO') {
                return $this->resultado(
                    false,
                    'TENTATIVA_ANTERIOR_NAO_CANCELADA'
                );
            }

            $segmentos = DB::table('smpp_segmentos_envio')
                ->where('smpp_tentativa_envio_id', $tentativa->id)
                ->orderBy('numero_segmento')
                ->get();

            if ($segmentos->count() !== (int) $sms->segmentos) {
                return $this->resultado(
                    false,
                    'SEGMENTOS_ANTERIORES_INCOMPATIVEIS'
                );
            }

            foreach ($segmentos as $segmento) {

                if (
                    $segmento->estado !== 'PREPARADO' ||
                    $segmento->submetido_em !== null ||
                    $segmento->respondido_em !== null ||
                    $segmento->provider_message_id !== null
                ) {
                    return $this->resultado(
                        false,
                        'SEGMENTO_ANTERIOR_INICIADO'
                    );
                }
            }
        }

        return $this->resultado(
            true,
            'RETRY_SEM_ENVIO_ANTERIOR'
        );
    }

    /**
     * Resultado padronizado.
     */
    private function resultado(
        bool $permitido,
        string $motivo
    ): array {
        return [
            'permitido' => $permitido,
            'motivo' => $motivo,
            'max_tentativas' => self::MAX_TENTATIVAS,
            'intervalo_minutos' => self::INTERVALO_MINUTOS,
        ];
    }

    /**
     * Autoriza uma tentativa SMPP.
     *
     * Suporta:
     *
     * PRE_PAGO:
     * - CLIENTE
     * - STOCK
     *
     * POS_PAGO:
     * - POS_PAGO
     * - STOCK
     *
     * Não transmite mensagens nem movimenta créditos.
     */
    public function autorizar(int $smsId): array
    {
        return DB::transaction(function () use ($smsId): array {

            /*
            |--------------------------------------------------------------------------
            | 1. BLOQUEAR SMS
            |--------------------------------------------------------------------------
            */
            $sms = DB::table('sms')
                ->where('id', $smsId)
                ->lockForUpdate()
                ->first();

            if (!$sms) {
                return $this->resultado(
                    false,
                    'SMS_NAO_ENCONTRADA'
                );
            }

            if ($sms->estado !== 'PENDENTE') {
                return $this->resultado(
                    false,
                    'SMS_NAO_PENDENTE'
                );
            }

            if ($sms->custeado_por !== 'CLIENTE') {
                return $this->resultado(
                    false,
                    'FINANCIAMENTO_NAO_SUPORTADO'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 2. VERIFICAR INTERVALO
            |--------------------------------------------------------------------------
            */
            if (
                $sms->proxima_tentativa_em !== null &&
                $sms->proxima_tentativa_em > now()->toDateTimeString()
            ) {
                return $this->resultado(
                    false,
                    'AGUARDAR_INTERVALO'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 3. VALIDAR CONTA
            |--------------------------------------------------------------------------
            */
            $conta = DB::table('contas')
                ->where('id', $sms->conta_id)
                ->lockForUpdate()
                ->first();

            if (!$conta) {
                return $this->resultado(
                    false,
                    'CONTA_NAO_ENCONTRADA'
                );
            }

            if ($conta->estado !== 'ACTIVA') {
                return $this->resultado(
                    false,
                    'CONTA_INACTIVA'
                );
            }

            if (
                !in_array(
                    $conta->tipo_cobranca,
                    ['PRE_PAGO', 'POS_PAGO'],
                    true
                )
            ) {
                return $this->resultado(
                    false,
                    'COBRANCA_NAO_SUPORTADA'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 4. IDENTIFICAR RESERVA FINANCEIRA
            |--------------------------------------------------------------------------
            */
            $tipoFinanceiro = $conta->tipo_cobranca === 'POS_PAGO'
                ? 'POS_PAGO'
                : 'CLIENTE';

            /*
            |--------------------------------------------------------------------------
            | 5. VALIDAR TENTATIVAS ANTERIORES
            |--------------------------------------------------------------------------
            */
            $tentativas = DB::table('smpp_tentativas_envio')
                ->where('sms_id', $smsId)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($tentativas->count() >= self::MAX_TENTATIVAS) {
                return $this->resultado(
                    false,
                    'LIMITE_TENTATIVAS'
                );
            }

            foreach ($tentativas as $tentativa) {

                if ($tentativa->estado !== 'CANCELADA_SEM_ENVIO') {
                    return $this->resultado(
                        false,
                        'TENTATIVA_ANTERIOR_NAO_CANCELADA'
                    );
                }

                $segmentos = DB::table('smpp_segmentos_envio')
                    ->where(
                        'smpp_tentativa_envio_id',
                        $tentativa->id
                    )
                    ->orderBy('numero_segmento')
                    ->lockForUpdate()
                    ->get();

                if (
                    $segmentos->count() !==
                    (int) $sms->segmentos
                ) {
                    return $this->resultado(
                        false,
                        'SEGMENTOS_ANTERIORES_INCOMPATIVEIS'
                    );
                }

                foreach ($segmentos as $segmento) {

                    if (
                        $segmento->estado !== 'PREPARADO' ||
                        $segmento->submetido_em !== null ||
                        $segmento->respondido_em !== null ||
                        $segmento->provider_message_id !== null
                    ) {
                        return $this->resultado(
                            false,
                            'SEGMENTO_ANTERIOR_INICIADO'
                        );
                    }
                }
            }

            /*
            |--------------------------------------------------------------------------
            | 6. BLOQUEAR RESERVAS
            |--------------------------------------------------------------------------
            */
            $reservas = DB::table('sms_reservas')
                ->where('sms_id', $smsId)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $total = (int) $sms->segmentos;

            if ($total < 1 || $reservas->isEmpty()) {
                return $this->resultado(
                    false,
                    'RESERVAS_INVALIDAS'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 7. VALIDAR TIPOS E ESTADOS
            |--------------------------------------------------------------------------
            */
            if (
                $reservas->contains(
                    fn ($r) =>
                        $r->estado !== 'RESERVADA' ||
                        (int) $r->quantidade < 1 ||
                        !in_array(
                            $r->tipo,
                            [$tipoFinanceiro, 'STOCK'],
                            true
                        )
                )
            ) {
                return $this->resultado(
                    false,
                    'RESERVAS_INVALIDAS'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 8. VALIDAR QUANTIDADES
            |--------------------------------------------------------------------------
            */
            $financeiras = $reservas->where(
                'tipo',
                $tipoFinanceiro
            );

            $stock = $reservas->where(
                'tipo',
                'STOCK'
            );

            if (
                $financeiras->isEmpty() ||
                $stock->isEmpty() ||
                (int) $financeiras->sum('quantidade') !== $total ||
                (int) $stock->sum('quantidade') !== $total ||
                $reservas->count() !==
                    $financeiras->count() + $stock->count()
            ) {
                return $this->resultado(
                    false,
                    'RESERVAS_INCOMPATIVEIS'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 9. VALIDAR CICLO POS_PAGO
            |--------------------------------------------------------------------------
            */
            if ($tipoFinanceiro === 'POS_PAGO') {

                if ($financeiras->count() !== 1) {
                    return $this->resultado(
                        false,
                        'RESERVA_POS_PAGO_INVALIDA'
                    );
                }

                $reserva = $financeiras->first();

                if (!$reserva->ciclo_consumo_sms_id) {
                    return $this->resultado(
                        false,
                        'CICLO_POS_PAGO_INEXISTENTE'
                    );
                }

                $ciclo = DB::table('ciclos_consumo_sms')
                    ->where(
                        'id',
                        $reserva->ciclo_consumo_sms_id
                    )
                    ->where(
                        'conta_id',
                        $conta->id
                    )
                    ->lockForUpdate()
                    ->first();

                if (
                    !$ciclo ||
                    $ciclo->estado !== 'ABERTO' ||
                    (int) $ciclo->segmentos_reservados < $total
                ) {
                    return $this->resultado(
                        false,
                        'CICLO_POS_PAGO_INVALIDO'
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | 10. AUTORIZAR
            |--------------------------------------------------------------------------
            */
            return $this->resultado(
                true,
                $tentativas->isEmpty()
                    ? 'PRIMEIRA_TENTATIVA'
                    : 'RETRY_SEM_ENVIO_ANTERIOR'
            );

        }, 3);
    }
}