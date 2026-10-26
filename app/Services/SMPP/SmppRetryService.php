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

            $segmentos = DB::table('smpp_segmentos_envio')
                ->where(
                    'smpp_tentativa_envio_id',
                    $tentativa->id
                )
                ->get();

            if ($segmentos->isEmpty()) {
                return $this->resultado(
                    false,
                    'SEM_EVIDENCIA_DE_SEGMENTOS'
                );
            }

            /*
             * Qualquer segmento iniciado bloqueia
             * o retry nesta primeira versão.
             */
            foreach ($segmentos as $segmento) {

                if ($segmento->estado !== 'PREPARADO') {
                    return $this->resultado(
                        false,
                        'SEGMENTO_INICIADO_OU_RESULTADO_INCERTO'
                    );
                }

                if ($segmento->submetido_em !== null) {
                    return $this->resultado(
                        false,
                        'SEGMENTO_COM_REGISTO_DE_SUBMISSAO'
                    );
                }
            }

            if ($tentativa->estado !== 'PREPARADA') {
                return $this->resultado(
                    false,
                    'TENTATIVA_NAO_PREPARADA'
                );
            }
        }

        return $this->resultado(
            true,
            'TENTATIVAS_ANTERIORES_NAO_INICIADAS'
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
}