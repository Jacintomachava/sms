<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pagamentos', function (Blueprint $table) {
            $table->string('chave_idempotencia', 100)
                ->nullable()
                ->after('referencia');
            $table->unique(
                'chave_idempotencia',
                'pagamentos_chave_idempotencia_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('pagamentos', function (Blueprint $table) {
            $table->dropUnique(
                'pagamentos_chave_idempotencia_unique'
            );

            $table->dropColumn('chave_idempotencia');
        });
    }
};
