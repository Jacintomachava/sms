<?php

namespace App\Services\SMPP;

use App\Models\Sms;
use App\Models\SmsOutbox;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SmppEnvioService
{
    /**
     * Consulta uma SMS elegível para preparação.
     *
     * Não transmite mensagens à operadora.
     */
    public function proximaSms(): ?Sms
    {
        $outbox = SmsOutbox::query()
            ->where('estado', 'PENDENTE')
            ->whereHas('sms', function ($query) {
                $query->where('estado', 'PENDENTE');
            })
            ->whereNotIn(
                'sms_id',
                DB::table('smpp_tentativas_envio')
                    ->select('sms_id')
            )
            ->orderBy('id')
            ->first();

        return $outbox?->sms;
    }

    /**
     * Valida se a SMS pode ser submetida
     * através do SmppService actual.
     */
    public function validar(Sms $sms): void
    {
        if ($sms->estado !== 'PENDENTE') {
            throw new RuntimeException(
                'A SMS não está pendente.'
            );
        }

        if ((int) $sms->segmentos !== 1) {
            throw new RuntimeException(
                'Esta versão suporta apenas SMS de um segmento.'
            );
        }

        $mensagem = (string) $sms->mensagem;

        if ($mensagem === '' || strlen($mensagem) > 160 || preg_match('/[^\x20-\x7E]/', $mensagem) || strpbrk($mensagem, '@$[]\\^{}|~`') !== false) {
            throw new RuntimeException(
                'Mensagem incompatível com o SMPP actual.'
            );
        }

        if ($sms->custeado_por !== 'CLIENTE') {
            throw new RuntimeException(
                'Financiamento da SMS ainda não suportado.'
            );
        }
    }

    /**
     * Verifica se existem reservas financeiras
     * suficientes para esta SMS.
     */
    public function verificarReservas(Sms $sms): void
    {
        $reservas = DB::table('sms_reservas')
            ->where('sms_id', $sms->id)
            ->where('estado', 'RESERVADA')
            ->selectRaw('tipo, SUM(quantidade) AS total')
            ->groupBy('tipo')
            ->pluck('total', 'tipo');

        $segmentos = (int) $sms->segmentos;

        if ((int) ($reservas['CLIENTE'] ?? 0) !== $segmentos || (int) ($reservas['STOCK'] ?? 0) !== $segmentos) {
            throw new RuntimeException(
                'Reservas financeiras insuficientes.'
            );
        }
    }

    /**
     * Executa somente as verificações.
     *
     * Não cria tentativa nem envia SMS.
     */
    public function preparar(int $smsId): Sms
    {
        $sms = Sms::query()->findOrFail($smsId);

        $this->validar($sms);

        $this->verificarReservas($sms);

        $existeTentativa = DB::table('smpp_tentativas_envio')
            ->where('sms_id', $smsId)
            ->exists();

        if ($existeTentativa) {
            throw new RuntimeException(
                'Esta SMS já possui uma tentativa SMPP.'
            );
        }

        return $sms;
    }
}
