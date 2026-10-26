<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE smpp_tentativas_envio
            MODIFY COLUMN estado ENUM(
                'PREPARADA',
                'SUBMETENDO',
                'ACEITE',
                'REJEITADA',
                'INCERTA',
                'CANCELADA_SEM_ENVIO'
            ) NOT NULL DEFAULT 'PREPARADA'
        ");
    }

    public function down(): void
    {
        $existe = DB::table('smpp_tentativas_envio')
            ->where('estado', 'CANCELADA_SEM_ENVIO')
            ->exists();

        if ($existe) {
            throw new RuntimeException(
                'Não é possível reverter: existem tentativas canceladas sem envio.'
            );
        }

        DB::statement("
            ALTER TABLE smpp_tentativas_envio
            MODIFY COLUMN estado ENUM(
                'PREPARADA',
                'SUBMETENDO',
                'ACEITE',
                'REJEITADA',
                'INCERTA'
            ) NOT NULL DEFAULT 'PREPARADA'
        ");
    }
};