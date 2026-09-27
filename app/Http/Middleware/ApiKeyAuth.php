<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuth
{
    public function handle(Request $request, Closure $next): Response {

        $plainKey = $request->header('X-API-Key');

        if (!$plainKey) {

            return response()->json([
                'status' => 0,
                'message' => 'A chave da API é obrigatória.',
                'error' => [
                    'code' => 'API_KEY_MISSING',
                ],
            ], 401);
        }

        $hash = hash('sha256', $plainKey);

        $apiKey = ApiKey::query()
            ->with('conta')
            ->where('key_hash', $hash)
            ->where('estado', 'ACTIVA')
            ->first();


        if (!$apiKey) {

            return response()->json([
                'status' => 0,
                'message' => 'A chave da API é inválida ou foi revogada.',
                'error' => [
                    'code' => 'INVALID_API_KEY',
                ],
            ], 401);
        }

        if (!$apiKey->conta || $apiKey->conta->estado !== 'ACTIVA') {

            return response()->json([
                'status' => 0,
                'message' => 'A conta encontra-se indisponível.',
                'error' => [
                    'code' => 'ACCOUNT_INACTIVE',
                ],
            ], 403);
        }

        /*
         * Disponibiliza os dados para todos
         * os Controllers da API.
         */
        $request->attributes->set('api_key', $apiKey);
        $request->attributes->set('conta', $apiKey->conta);
        $request->attributes->set('conta_id', $apiKey->conta_id);

        /*
         * Informação de auditoria.
         *
         * Não precisamos bloquear a resposta
         * por causa desta actualização.
         */
        $apiKey->update([
            'ultimo_uso_em' => now(),
            'ultimo_ip' => $request->ip(),
        ]);

        return $next($request);
    }
}