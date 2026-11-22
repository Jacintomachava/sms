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
        Schema::create('otp_configuracoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conta_id')
                ->unique()
                ->constrained('contas');
            $table->foreignId('sender_id')
                ->nullable()
                ->constrained('sender_ids')
                ->nullOnDelete();
            $table->unsignedTinyInteger('tamanho_codigo')->default(6);
            $table->unsignedSmallInteger('validade_minutos')->default(5);
            $table->unsignedTinyInteger('max_tentativas')->default(5);
            $table->unsignedSmallInteger('intervalo_reenvio_segundos')->default(60);
            $table->boolean('activo')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otp_configuracoes');
    }
};
