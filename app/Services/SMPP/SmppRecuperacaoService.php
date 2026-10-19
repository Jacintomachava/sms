<?php

namespace App\Services\SMPP;

use App\Models\Sms;
use App\Models\SmppTentativaEnvio;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SmppRecuperacaoService
{
    /**
     * Marca como incertas as tentativas sem resposta
     * confirmada de uma sessão SMPP encerrada.
     */
    public function recuperarSessao(string $sessionId): array
    {
        if (trim($sessionId) === '') {
            throw new RuntimeException(
                'Identificador da sessão SMPP inválido.'
            );
        }

        $ids = SmppTentativaEnvio::query()
            ->where('session_id', $sessionId)
            ->where('estado', 'SUBMETENDO')
            ->orderBy('id')
            ->pluck('id');

        $recuperadas = 0;
        $ignoradas = 0;
        $erros = [];

        foreach ($ids as $tentativaId) {
            try {
                
                $alterada = $this->marcarTentativaIncerta((int) $tentativaId);

                if ($alterada) {
                    $recuperadas++;
                } else {
                    $ignoradas++;
                }

            } catch (\Throwable $e) {
                $erros[] = [
                    'tentativa_id' => $tentativaId,
                    'erro' => $e->getMessage(),
                ];

                Log::error('Falha na recuperação SMPP', [
                    'tentativa_id' => $tentativaId,
                    'session_id' => $sessionId,
                    'erro' => $e->getMessage(),
                ]);
            }
        }

        return [
            'recuperadas' => $recuperadas,
            'ignoradas' => $ignoradas,
            'erros' => $erros,
        ];
    }

    /**
     * Actualiza SMS e tentativa numa única transacção.
     *
     * Ordem de bloqueio: SMS -> Tentativa.
     */
    public function marcarTentativaIncerta(int $tentativaId): bool
    {
        return DB::transaction(function () use ($tentativaId) {

            // Consulta inicial sem bloqueio.
            $referencia = SmppTentativaEnvio::query()
                ->findOrFail($tentativaId);

            // Bloquear primeiro a SMS.
            $sms = Sms::query()
                ->whereKey($referencia->sms_id)
                ->lockForUpdate()
                ->firstOrFail();

            // Bloquear depois a tentativa.
            $tentativa = SmppTentativaEnvio::query()
                ->whereKey($tentativaId)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $tentativa->sms_id !== (int) $sms->id) {
                throw new RuntimeException(
                    'Associação entre SMS e tentativa alterada.'
                );
            }

            // Outra operação já confirmou o resultado.
            if (in_array($tentativa->estado, [
                'ACEITE',
                'REJEITADA',
                'INCERTA',
            ], true)) {
                return false;
            }

            if ($tentativa->estado !== 'SUBMETENDO') {
                throw new RuntimeException(
                    'Estado da tentativa incompatível.'
                );
            }

            if ($sms->estado === 'PROCESSANDO') {
                $sms->update([
                    'estado' => 'INCERTO',
                ]);
            } elseif ($sms->estado !== 'INCERTO') {
                throw new RuntimeException(
                    'Estado da SMS incompatível com recuperação.'
                );
            }

            $tentativa->update([
                'estado' => 'INCERTA',
                'erro' => 'Ligação SMPP encerrada sem confirmação definitiva.',
            ]);

            return true;

        }, 3);
    }
}