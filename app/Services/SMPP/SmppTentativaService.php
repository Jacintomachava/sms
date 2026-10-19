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
                'estado' => 'SUBMETENDO',
                'iniciada_em' => now(),
            ]);

            DB::table('sms')
                ->where('id', $smsId)
                ->update([
                    'estado' => 'PROCESSANDO',
                    'updated_at' => now(),
                ]);

            return $tentativa;

        }, 3);
    }
}