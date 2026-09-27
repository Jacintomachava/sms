<?php

namespace App\Services;

use App\Models\SenderId;
use App\Models\SenderIdHistorico;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SenderIdService
{
    public function alterarEstado(SenderId $sender,
        string $novoEstado,
        ?int $userId = null,
        ?string $observacao = null
    ): SenderId {

        $estadosPermitidos = [
            'PENDENTE',
            'EM_APROVACAO_OPERADORA',
            'APROVADO',
            'REJEITADO',
            'SUSPENSO',
        ];

        if (!in_array($novoEstado, $estadosPermitidos, true)) {
            throw new RuntimeException(
                'Estado do Sender ID inválido.'
            );
        }

        return DB::transaction(function () use (
            $sender,
            $novoEstado,
            $userId,
            $observacao
        ) {

            $sender = SenderId::query()
                ->whereKey($sender->id)
                ->lockForUpdate()
                ->firstOrFail();

            $estadoAnterior = $sender->estado;

            if ($estadoAnterior === $novoEstado) {
                return $sender;
            }

            $sender->estado = $novoEstado;

            switch ($novoEstado) {

                case 'EM_APROVACAO_OPERADORA':

                    $sender->analisado_por = $userId;
                    $sender->analisado_em = now();

                    break;

                case 'APROVADO':

                    $sender->aprovado_em = now();
                    $sender->suspenso_em = null;

                    break;

                case 'REJEITADO':

                    $sender->analisado_por = $userId;
                    $sender->analisado_em = now();

                    break;

                case 'SUSPENSO':

                    $sender->suspenso_em = now();

                    break;
            }

            $sender->save();

            SenderIdHistorico::create([
                'sender_id' => $sender->id,
                'estado_anterior' => $estadoAnterior,
                'estado_novo' => $novoEstado,
                'observacao' => $observacao,
                'user_id' => $userId,
            ]);

            return $sender->fresh();
        });
    }
}