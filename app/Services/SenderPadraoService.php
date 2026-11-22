<?php

namespace App\Services;

use App\Models\Conta;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SenderPadraoService
{
    public function associar(Conta $conta): void
    {
        /*
        |--------------------------------------------------------------------------
        | 1. LOCALIZAR SENDER PARTILHADO
        |--------------------------------------------------------------------------
        */
        $sender = DB::table('sender_ids')
            ->where('sender', 'InfoSMS')
            ->where('tipo', 'COMPARTILHADO')
            ->where('estado', 'APROVADO')
            ->first();

        if (!$sender) {
            throw new RuntimeException(
                'O Sender partilhado InfoSMS não está configurado ou aprovado.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 2. VERIFICAR ASSOCIAÇÃO EXISTENTE
        |--------------------------------------------------------------------------
        */
        $existe = DB::table('conta_sender_ids')
            ->where('conta_id', $conta->id)
            ->where('sender_id', $sender->id)
            ->exists();

        if ($existe) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | 3. ASSOCIAR SENDER À CONTA
        |--------------------------------------------------------------------------
        */
        DB::table('conta_sender_ids')->insert([
            'conta_id' => $conta->id,
            'sender_id' => $sender->id,
            'estado' => 'ACTIVO',
            'atribuido_por' => null,
            'atribuido_em' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
