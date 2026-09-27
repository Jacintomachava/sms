<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SenderId;
use Illuminate\Http\Request;

class SenderController extends Controller
{
    public function index(Request $request)
    {
        $contaId = (int) $request ->attributes->get('conta_id');

        $senders = SenderId::query()
            ->where('estado','APROVADO')
            ->whereHas('contas',

                function ($query) use ($contaId) {

                    $query
                        ->where('contas.id', $contaId)
                        ->where('conta_sender_ids.estado','ACTIVO');

                }
            )
            ->orderBy('sender')
            ->get();

        $data = $senders->map(
            function ($sender) {

                return [
                    //'id' => $sender->id,
                    'sender' => $sender->sender,

                    'type' =>
                        match ($sender->tipo) {

                            'EXCLUSIVO' => 'EXCLUSIVE',
                            'COMPARTILHADO' => 'SHARED',

                            default =>
                                $sender->tipo,
                        },

                    //'operator' => $sender->operadora,
                    'status' => 'ACTIVE',
                ];
            }
        );

        return response()->json([
            'status' => 1,
            'message' => 'Senders consultados com sucesso.',
            'data' => $data,
        ]);
    }
}