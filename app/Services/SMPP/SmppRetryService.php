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
     * Verifica, numa transacção, se uma SMS pode
     * ser considerada para uma nova tentativa.
     *
     * Não cria tentativas e não transmite PDUs.
     */
    public function autorizar(int $smsId): array
    {
        return DB::transaction(function () use ($smsId): array {

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

            /*
            * Primeira tentativa.
            */
            if ($tentativas->isEmpty()) {
                return $this->resultado(
                    true,
                    'PRIMEIRA_TENTATIVA'
                );
            }

            /*
            * Nesta fase, bloqueamos o retry.
            *
            * Uma tentativa PREPARADA pode ainda
            * pertencer a um worker activo.
            *
            * A autorização definitiva exigirá
            * comprovação de encerramento da sessão.
            */
            return $this->resultado(
                false,
                'RETRY_AGUARDA_ENCERRAMENTO_SEGURO'
            );

        }, 3);
    }
}