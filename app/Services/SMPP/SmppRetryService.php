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
         * Não basta o estado da tentativa.
         * Precisamos de analisar todos os segmentos.
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
                ->get();

            if ($segmentos->isEmpty()) {
                return $this->resultado(
                    false,
                    'TENTATIVA_SEM_SEGMENTOS'
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

        return $this->resultado(
            false,
            'AGUARDA_CONFIRMACAO_DE_ABANDONO'
        );
    }

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
     * Avalia uma nova tentativa sob bloqueio transaccional.
     *
     * IMPORTANTE:
     * Este método apenas verifica condições.
     * Não reserva créditos, não cria tentativas e não envia PDUs.
     *
     * A criação definitiva da tentativa terá de repetir
     * estas verificações na mesma transacção.
     */
    public function autorizar(int $smsId): array
    {
        return DB::transaction(function () use ($smsId): array {

            $sms = DB::table('sms')
                ->where('id', $smsId)
                ->lockForUpdate()
                ->first();

            if (!$sms) {
                return $this->resultado(false, 'SMS_NAO_ENCONTRADA');
            }

            if ($sms->estado !== 'PENDENTE') {
                return $this->resultado(false, 'SMS_NAO_PENDENTE');
            }

            if ($sms->custeado_por !== 'CLIENTE') {
                return $this->resultado(false, 'FINANCIAMENTO_NAO_SUPORTADO');
            }

            if (
                $sms->proxima_tentativa_em !== null &&
                $sms->proxima_tentativa_em > now()->toDateTimeString()
            ) {
                return $this->resultado(false, 'AGUARDAR_INTERVALO');
            }

            $tentativas = DB::table('smpp_tentativas_envio')
                ->where('sms_id', $smsId)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($tentativas->count() >= self::MAX_TENTATIVAS) {
                return $this->resultado(false, 'LIMITE_TENTATIVAS');
            }

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
                    ->lockForUpdate()
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

            $reservas = DB::table('sms_reservas')
                ->where('sms_id', $smsId)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if (
                $reservas->isEmpty() ||
                $reservas->contains(
                    fn ($r) =>
                        $r->estado !== 'RESERVADA' ||
                        (int) $r->quantidade < 1 ||
                        !in_array($r->tipo, ['CLIENTE', 'STOCK'], true)
                )
            ) {
                return $this->resultado(false, 'RESERVAS_INVALIDAS');
            }

            $total = (int) $sms->segmentos;

            if (
                (int) $reservas->where('tipo', 'CLIENTE')->sum('quantidade') !== $total ||
                (int) $reservas->where('tipo', 'STOCK')->sum('quantidade') !== $total
            ) {
                return $this->resultado(
                    false,
                    'RESERVAS_INCOMPATIVEIS'
                );
            }

            return $this->resultado(
                true,
                $tentativas->isEmpty()
                    ? 'PRIMEIRA_TENTATIVA'
                    : 'RETRY_SEM_ENVIO_ANTERIOR'
            );

        }, 3);
    }
}