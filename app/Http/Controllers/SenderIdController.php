<?php

namespace App\Http\Controllers;

use App\Models\SenderId;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Throwable;

class SenderIdController extends Controller
{
    /**
     * Listar Sender IDs da conta actual.
     */
    public function index(Request $request)
    {
        $contaId = $request->session()->get('conta_id');

        $senders = SenderId::query()
            ->whereHas('contas', function ($query) use ($contaId) {
                $query->where('contas.id', $contaId);
            })
            ->with([
                'contas' => function ($query) use ($contaId) {
                    $query->where('contas.id', $contaId);
                }
            ])
            ->latest('sender_ids.created_at')
            ->paginate(15);

        return view('sender_ids.index', compact('senders'));
    }


    /**
     * Solicitar novo Sender ID.
     */
    public function store(Request $request)
    {
        $contaId = $request->session()->get('conta_id');

        $dados = $request->validate([
            'sender' => [
                'required',
                'string',
                'min:3',
                'max:50',

                /*
                 * Letras, números, espaço, hífen e underscore.
                 *
                 * Quando tivermos as regras definitivas da Movitel,
                 * podemos restringir mais.
                 */
                'regex:/^[A-Za-z0-9 _-]+$/',
            ],
            'descricao' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        try {

            return DB::transaction(function () use (
                $dados,
                $contaId
            ) {

                /*
                |--------------------------------------------------------------------------
                | NORMALIZAR
                |--------------------------------------------------------------------------
                */

                $senderNome = strtoupper(trim($dados['sender']));

                /*
                |--------------------------------------------------------------------------
                | VERIFICAR SE JÁ EXISTE
                |--------------------------------------------------------------------------
                */
                $senderExistente = SenderId::query()->where('sender', $senderNome)->first();

                if ($senderExistente) {

                    /*
                    |--------------------------------------------------------------------------
                    | JÁ PERTENCE À CONTA?
                    |--------------------------------------------------------------------------
                    */
                    $jaAssociado = DB::table('conta_sender_ids')->where('conta_id', $contaId)->where('sender_id', $senderExistente->id)->exists();

                    if ($jaAssociado) {
                        return response()->json([
                            'status' => 0,
                            'message' => 'Este Sender ID já está associado à sua conta.',
                        ], 422);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | SENDER EXCLUSIVO
                    |--------------------------------------------------------------------------
                    |
                    | Um Sender exclusivo existente não pode ser solicitado
                    | por outra conta.
                    */
                    if ($senderExistente->tipo === 'EXCLUSIVO') {
                        return response()->json([
                            'status' => 0,
                            'message' => 'Este Sender ID já se encontra registado.',
                        ], 422);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | COMPARTILHADO
                    |--------------------------------------------------------------------------
                    |
                    | Não vamos associar automaticamente.
                    | A INFORDATA deverá autorizar.
                    */
                    return response()->json([
                        'status' => 0,
                        'message' => 'Este Sender ID é compartilhado. Solicite à INFORDATA a sua atribuição.',
                    ], 422);
                }

                /*
                |--------------------------------------------------------------------------
                | CRIAR SENDER EXCLUSIVO
                |--------------------------------------------------------------------------
                */
                $sender = SenderId::create([
                    'sender' => $senderNome,
                    'descricao' => $dados['descricao'] ?? null,
                    'tipo' => 'EXCLUSIVO',
                    'estado' => 'PENDENTE',
                ]);

                /*
                |--------------------------------------------------------------------------
                | ASSOCIAR À CONTA
                |--------------------------------------------------------------------------
                */
                DB::table('conta_sender_ids')->insert([
                    'conta_id' => $contaId,
                    'sender_id' => $sender->id,
                    'estado' => 'ACTIVO',
                    'atribuido_por' => auth()->id(),
                    'atribuido_em' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return response()->json([
                    'status' => 1,
                    'message' => 'Sender ID solicitado com sucesso.',
                ]);
            });

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status' => 0,
                'message' => 'Não foi possível solicitar o Sender ID.',
            ], 500);
        }
    }
}