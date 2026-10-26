<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_reconciliacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sms_id')->constrained('sms')->restrictOnDelete();
            $table->foreignId('smpp_tentativa_envio_id')->nullable()->constrained('smpp_tentativas_envio')->restrictOnDelete();
            $table->enum('estado', [
                'PENDENTE',
                'EM_ANALISE',
                'RESOLVIDA',
                'MANUAL'
            ])->default('PENDENTE');
            $table->unsignedInteger('tentativas')->default(0);
            $table->timestamp('proxima_verificacao_em')->nullable();
            $table->timestamp('ultima_verificacao_em')->nullable();
            $table->string('motivo', 255)->nullable();
            $table->text('observacao')->nullable();
            $table->string('resultado', 50)->nullable();
            $table->timestamp('resolvida_em')->nullable();
            $table->timestamps();
            $table->unique('sms_id');

            $table->index([
                'estado',
                'proxima_verificacao_em'
            ], 'sms_reconciliacoes_agendamento_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_reconciliacoes');
    }
};