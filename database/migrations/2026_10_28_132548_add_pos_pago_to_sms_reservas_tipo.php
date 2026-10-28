<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE sms_reservas
            MODIFY COLUMN tipo
            ENUM('CLIENTE','STOCK','POS_PAGO')
            NOT NULL
        ");
    }

    public function down(): void
    {
        $existe = DB::table('sms_reservas')
            ->where('tipo', 'POS_PAGO')
            ->exists();

        if ($existe) {
            throw new RuntimeException(
                'Não é possível remover POS_PAGO enquanto existirem reservas desse tipo.'
            );
        }

        DB::statement("
            ALTER TABLE sms_reservas
            MODIFY COLUMN tipo
            ENUM('CLIENTE','STOCK')
            NOT NULL
        ");
    }
};