<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conta;
use App\Models\SenderId;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class SenderIdController extends Controller
{
    /**
     * Listar todos os Sender IDs da plataforma.
     */
    public function index()
    {
        $senders = SenderId::query()->withCount('contas')->latest()->paginate(20);

        return view(
            'admin.sender_ids.index',
            compact('senders')
        );
    }


    /**
     * Colocar Sender ID em aprovação pela operadora.
     */
    public function enviarOperadora(SenderId $sender)
    {
        if ($sender->estado !== 'PENDENTE') {

            return response()->json([
                'status' => 0,
                'message' => 'Apenas Sender IDs pendentes podem ser enviados para aprovação.',
            ], 422);
        }

        $sender->update([
            'estado' => 'EM_APROVACAO_OPERADORA',
            'analisado_por' => auth()->id(),
            'analisado_em' => now(),
            'operadora' => 'MOVITEL',
        ]);

        return response()->json([
            'status' => 1,
            'message' => 'Sender ID colocado em aprovação pela operadora.',
        ]);
    }


    /**
     * Aprovar Sender ID.
     */
    public function aprovar(SenderId $sender)
    {
        if (
            !in_array(
                $sender->estado,
                [
                    'PENDENTE',
                    'EM_APROVACAO_OPERADORA'
                ],
                true
            )
        ) {

            return response()->json([
                'status' => 0,
                'message' => 'Este Sender ID não pode ser aprovado no estado actual.',
            ], 422);
        }

        $sender->update([
            'estado' => 'APROVADO',
            'analisado_por' => auth()->id(),
            'analisado_em' => now(),
            'aprovado_em' => now(),
            /*
             * Neste fluxo estamos a considerar que a aprovação
             * da operadora já foi confirmada.
             */
            'aprovado_operadora_em' => now(),
            'motivo_rejeicao' => null,
            'suspenso_em' => null,
        ]);

        return response()->json([
            'status' => 1,
            'message' => 'Sender ID aprovado com sucesso.',
        ]);
    }


    /**
     * Rejeitar Sender ID.
     */
    public function rejeitar(Request $request, SenderId $sender) {

        $dados = $request->validate([
            'motivo' => ['required','string','max:1000'],
        ]);

        if ($sender->estado === 'APROVADO') {

            return response()->json([
                'status' => 0,
                'message' => 'Um Sender ID aprovado deve ser suspenso, não rejeitado.',
            ], 422);
        }

        $sender->update([
            'estado' => 'REJEITADO',
            'motivo_rejeicao' => $dados['motivo'],
            'analisado_por' => auth()->id(),
            'analisado_em' => now(),
        ]);

        return response()->json([
            'status' => 1,
            'message' => 'Sender ID rejeitado.',
        ]);
    }


    /**
     * Suspender Sender ID aprovado.
     */
    public function suspender(Request $request, SenderId $sender) {

        $dados = $request->validate([
            'motivo' => [
                'required',
                'string',
                'max:1000'
            ],
        ]);

        if ($sender->estado !== 'APROVADO') {

            return response()->json([
                'status' => 0,
                'message' => 'Apenas Sender IDs aprovados podem ser suspensos.',
            ], 422);
        }

        $sender->update([
            'estado' => 'SUSPENSO',
            'observacao' => $dados['motivo'],
            'suspenso_em' => now(),
            'analisado_por' => auth()->id(),
            'analisado_em' => now(),
        ]);

        return response()->json([
            'status' => 1,
            'message' => 'Sender ID suspenso.',
        ]);
    }


    /**
     * Reactivar Sender ID suspenso.
     */
    public function reactivar(SenderId $sender)
    {
        if ($sender->estado !== 'SUSPENSO') {

            return response()->json([
                'status' => 0,
                'message' => 'Apenas Sender IDs suspensos podem ser reactivados.',
            ], 422);
        }

        $sender->update([
            'estado' => 'APROVADO',
            'suspenso_em' => null,
            'analisado_por' => auth()->id(),
            'analisado_em' => now(),
        ]);

        return response()->json([
            'status' => 1,
            'message' => 'Sender ID reactivado.',
        ]);
    }
}