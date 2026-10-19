<?php

namespace App\Services\SMPP;

use App\Models\Sms;
use App\Models\SmppTentativaEnvio;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SmppRespostaService
{
    public function __construct(
        private readonly SmsConfirmacaoService $confirmacao
    ) {}

    public function processar(
        string $sessionId,
        int $sequenceNumber,
        int $commandStatus,
        ?string $providerMessageId
    ): void {
        if (
            trim($sessionId) === '' ||
            $sequenceNumber < 1 ||
            $commandStatus < 0 ||
            $commandStatus > 0xFFFFFFFF
        ) {
            throw new RuntimeException(
                'Identificação ou estado da resposta SMPP inválido.'
            );
        }

        DB::transaction(function () use (
            $sessionId,
            $sequenceNumber,
            $commandStatus,
            $providerMessageId
        ) {

            /*
            |--------------------------------------------------------------------------
            | 1. LOCALIZAR TENTATIVA SEM BLOQUEIO
            |--------------------------------------------------------------------------
            */
            $referencia = SmppTentativaEnvio::query()
                ->where('session_id', $sessionId)
                ->where('sequence_number', $sequenceNumber)
                ->first();

            if (!$referencia) {
                throw new RuntimeException(
                    'Tentativa SMPP não encontrada.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 2. BLOQUEAR SMS
            |--------------------------------------------------------------------------
            */
            $sms = Sms::query()
                ->whereKey($referencia->sms_id)
                ->lockForUpdate()
                ->firstOrFail();

            /*
            |--------------------------------------------------------------------------
            | 3. BLOQUEAR E REVALIDAR TENTATIVA
            |--------------------------------------------------------------------------
            */
            $tentativa = SmppTentativaEnvio::query()
                ->whereKey($referencia->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                (int) $tentativa->sms_id !== (int) $sms->id ||
                $tentativa->session_id !== $sessionId ||
                (int) $tentativa->sequence_number !== $sequenceNumber
            ) {
                throw new RuntimeException(
                    'Identificação da tentativa SMPP inconsistente.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 4. IDEMPOTÊNCIA
            |--------------------------------------------------------------------------
            */
            if ($tentativa->estado === 'ACEITE') {

                if (
                    $commandStatus !== 0 ||
                    $tentativa->provider_message_id !== $providerMessageId
                ) {
                    throw new RuntimeException(
                        'Conflito com resposta SMPP já aceite.'
                    );
                }

                return;
            }

            if ($tentativa->estado === 'REJEITADA') {

                if (
                    $commandStatus === 0 ||
                    (int) $tentativa->command_status !== $commandStatus
                ) {
                    throw new RuntimeException(
                        'Conflito com resposta SMPP já rejeitada.'
                    );
                }

                return;
            }

            if (!in_array($tentativa->estado, [
                'SUBMETENDO',
                'INCERTA',
            ], true)) {
                throw new RuntimeException(
                    'Estado da tentativa incompatível.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 5. RESPOSTA ACEITE
            |--------------------------------------------------------------------------
            */
            if ($commandStatus === 0) {

                if (
                    $providerMessageId === null ||
                    trim($providerMessageId) === ''
                ) {
                    throw new RuntimeException(
                        'Resposta aceite sem message_id.'
                    );
                }

                $this->confirmacao->confirmarAceite(
                    (int) $sms->id,
                    $providerMessageId
                );

                $tentativa->update([
                    'estado' => 'ACEITE',
                    'command_status' => 0,
                    'provider_message_id' => $providerMessageId,
                    'respondida_em' => now(),
                    'erro' => null,
                ]);

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | 6. RESPOSTA REJEITADA
            |--------------------------------------------------------------------------
            */
            $this->confirmacao->libertarRejeitada(
                (int) $sms->id
            );

            $tentativa->update([
                'estado' => 'REJEITADA',
                'command_status' => $commandStatus,
                'provider_message_id' => null,
                'respondida_em' => now(),
                'erro' => sprintf(
                    'SMPP command_status: 0x%08X',
                    $commandStatus
                ),
            ]);

        }, 3);
    }
}