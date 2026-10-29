<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facturas_sms', function (Blueprint $table) {
            $table->decimal('percentagem_multa', 5, 2)
                ->default(0);

            $table->decimal('valor_multa', 15, 2)
                ->default(0);

            $table->timestamp('multa_aplicada_em')
                ->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('facturas_sms', function (Blueprint $table) {
            $table->dropColumn([
                'percentagem_multa',
                'valor_multa',
                'multa_aplicada_em',
            ]);
        });
    }
};