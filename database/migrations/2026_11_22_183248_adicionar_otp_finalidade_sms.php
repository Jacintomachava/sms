
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE sms
            MODIFY COLUMN finalidade ENUM(
                'NORMAL',
                'NOTIFICACAO',
                'NOTIFICACAO_INTERNA',
                'CORTESIA_TESTE',
                'OTP'
            ) NOT NULL DEFAULT 'NORMAL'
        ");
    }

    public function down(): void
    {
        $existemOtp = DB::table('sms')
            ->where('finalidade', 'OTP')
            ->exists();

        if ($existemOtp) {
            throw new RuntimeException(
                'Não é possível remover OTP: existem SMS com esta finalidade.'
            );
        }

        DB::statement("
            ALTER TABLE sms
            MODIFY COLUMN finalidade ENUM(
                'NORMAL',
                'NOTIFICACAO',
                'NOTIFICACAO_INTERNA',
                'CORTESIA_TESTE'
            ) NOT NULL DEFAULT 'NORMAL'
        ");
    }
};
