<?php

namespace App\Services;

use App\Models\ApiKey;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ApiKeyService
{
    public function gerar(int $contaId, ?int $userId = null, string $nome = 'API Principal'): array {

        return DB::transaction(function () use (
            $contaId,
            $userId,
            $nome
        ) {
            /*
             * 32 bytes aleatórios = 256 bits.
             */
            $random = bin2hex(random_bytes(32));
            // Chave entregue ao cliente
            $plainKey = 'sms_live_' . $random;
            // Hash armazenado na BD
            $hash = hash('sha256', $plainKey);
            // Identificação visual no dashboard
            $prefixo = substr($plainKey, 0, 17). '...'. substr($plainKey, -4);

            $apiKey = ApiKey::create([
                'conta_id' => $contaId,
                'nome' => $nome,
                'key_hash' => $hash,
                'prefixo' => $prefixo,
                'estado' => 'ACTIVA',
                'criado_por' => $userId,
            ]);

            return [
                'api_key' => $apiKey,
                'plain_key' => $plainKey,
            ];
        });
    }
}