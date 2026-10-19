<?php

namespace App\Services\SMPP;

use App\Models\SmppTentativaEnvio;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SmppTentativaService
{
    public function preparar(int $smsId, string $sessionId, int $sequenceNumber, string $host, int $porta, string $workerId): SmppTentativaEnvio {

        if ($sessionId === '' || $sequenceNumber < 1 || $host === '' || $workerId === '') {
            throw new RuntimeException(
                'Dados da tentativa SMPP inválidos.'
            );
        }

        return DB::transaction(function () use (
            $smsId,
            $sessionId,
            $sequenceNumber,
            $host,
            $porta,
            $workerId
        ) {

            $sms = DB::table('sms')
                ->where('id', $smsId)
                ->lockForUpdate()
                ->first();

            if (!$sms) {
                throw new RuntimeException(
                    'SMS não encontrada.'
                );
            }

            if ($sms->estado !== 'PENDENTE') {
                throw new RuntimeException(
                    'A SMS não está pendente.'
                );
            }

            $segmentos = (int) $sms->segmentos;

            if ($segmentos < 1) {
                throw new RuntimeException(
                    'Número de segmentos inválido.'
                );
            }

            $reservas = DB::table('sms_reservas')->where('sms_id', $smsId)->lockForUpdate()->get();
            $stockReservado = $reservas->where('tipo', 'STOCK')->where('estado', 'RESERVADA')->sum('quantidade');
            $clienteReservado = $reservas->where('tipo', 'CLIENTE')->where('estado', 'RESERVADA')->sum('quantidade');

            if ((int) $stockReservado !== $segmentos) {
                throw new RuntimeException(
                    'A SMS não possui stock reservado suficiente.'
                );
            }

            if ($sms->custeado_por === 'CLIENTE' && (int) $clienteReservado !== $segmentos) {
                throw new RuntimeException(
                    'A SMS não possui créditos reservados suficientes.'
                );
            }

            if ($reservas->contains(
                fn ($reserva) =>
                    $reserva->estado !== 'RESERVADA' ||
                    (int) $reserva->quantidade < 1
            )) {
                throw new RuntimeException(
                    'Existem reservas inválidas para esta SMS.'
                );
            }

            $tentativaAnterior = SmppTentativaEnvio::query()
                ->where('sms_id', $smsId)
                ->exists();

            if ($tentativaAnterior) {
                throw new RuntimeException(
                    'A SMS já possui uma tentativa SMPP. '
                    . 'Não é permitido reenviar automaticamente.'
                );
            }

            $tentativa = SmppTentativaEnvio::create([
                'sms_id' => $smsId,
                'host' => $host,
                'porta' => $porta,
                'worker_id' => $workerId,
                'session_id' => $sessionId,
                'sequence_number' => $sequenceNumber,
                'estado' => 'PREPARADA',
                'iniciada_em' => now(),
            ]);

            /*DB::table('sms')
                ->where('id', $smsId)
                ->update([
                    'estado' => 'PROCESSANDO',
                    'updated_at' => now(),
                ]);  */

            return $tentativa;

        }, 3);
    }

    /**
     * Autoriza a passagem de PREPARADA para SUBMETENDO.
     *
     * Não transmite a PDU à operadora.
     *
     * ATENÇÃO:
     * Chamar apenas no fluxo real de transmissão,
     * imediatamente antes de sendPreparedSubmit().
     */
    public function iniciarSubmissao(int $tentativaId): void
    {
        DB::transaction(function () use ($tentativaId) {

            $referencia = SmppTentativaEnvio::query()
                ->findOrFail($tentativaId);

            // Manter a ordem de bloqueio SMS -> tentativa.
            $sms = DB::table('sms')
                ->where('id', $referencia->sms_id)
                ->lockForUpdate()
                ->first();

            if (!$sms) {
                throw new RuntimeException(
                    'SMS associada à tentativa não encontrada.'
                );
            }

            $tentativa = SmppTentativaEnvio::query()
                ->whereKey($tentativaId)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $tentativa->sms_id !== (int) $sms->id || $tentativa->estado !== 'PREPARADA' || $sms->estado !== 'PENDENTE') {
                throw new RuntimeException(
                    'Tentativa ou SMS em estado incompatível.'
                );
            }

            // Não iniciar sem reservas válidas.
            $reservas = DB::table('sms_reservas')
                ->where('sms_id', $sms->id)
                ->where('estado', 'RESERVADA')
                ->selectRaw('tipo, SUM(quantidade) AS total')
                ->groupBy('tipo')
                ->pluck('total', 'tipo');

            $segmentos = (int) $sms->segmentos;

            if ($segmentos !== 1 || $sms->custeado_por !== 'CLIENTE' || (int) ($reservas['CLIENTE'] ?? 0) !== $segmentos || (int) ($reservas['STOCK'] ?? 0) !== $segmentos) {
                throw new RuntimeException(
                    'Reservas ou financiamento incompatíveis.'
                );
            }

            $tentativa->update([
                'estado' => 'SUBMETENDO',
                'iniciada_em' => now(),
            ]);

            DB::table('sms')
                ->where('id', $sms->id)
                ->update([
                    'estado' => 'PROCESSANDO',
                    'updated_at' => now(),
                ]);

        }, 3);
    }

}