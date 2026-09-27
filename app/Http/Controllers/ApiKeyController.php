<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use App\Models\SenderId;
use App\Services\ApiKeyService;
use Illuminate\Http\Request;
use Throwable;

class ApiKeyController extends Controller
{
    public function index()
    {
        $contaId = (int) session('conta_id');

        $keys = ApiKey::query()->where('conta_id', $contaId)->latest()->get();

        /*
         * Vamos buscar um Sender activo para montar
         * automaticamente o exemplo cURL.
         */
        $sender = SenderId::query()
            ->where('estado', 'APROVADO')
            ->whereHas('contas', function ($query) use ($contaId) {
                $query
                    ->where('contas.id', $contaId)
                    ->where('conta_sender_ids.estado', 'ACTIVO');
            })
            ->orderBy('sender')
            ->first();

        return view('api-keys.index', compact('keys', 'sender'));

    }


    public function store(Request $request, ApiKeyService $service) {
        
        $request->validate([
            'nome' => ['nullable','string','max:100',],
        ]);

        try {

            $resultado = $service->gerar((int) session('conta_id'), auth()->id(), $request->nome ?: 'API Principal');

            return response()->json([
                'status' => 1,
                'message' => 'API Key gerada com sucesso.',

                'data' => [
                    'id' => $resultado['api_key']->id,
                    'name' => $resultado['api_key']->nome,
                    /*
                     * ÚNICO momento em que devolvemos
                     * a chave completa.
                     */
                    'api_key' => $resultado['plain_key'],
                ],
            ]);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status' => 0,
                'message' => 'Não foi possível gerar a API Key.',
            ], 500);
        }
    }

    public function revoke(int $id)
    {
        $contaId = (int) session('conta_id');

        $key = ApiKey::query() ->where('conta_id', $contaId) ->where('id', $id) ->where('estado', 'ACTIVA')->firstOrFail();

        $key->update([
            'estado' => 'REVOGADA',
            'revogada_em' => now(),
            'revogada_por' => auth()->id(),
        ]);

        return response()->json([
            'status' => 1,
            'message' => 'API Key revogada com sucesso.',
        ]);
    }
}