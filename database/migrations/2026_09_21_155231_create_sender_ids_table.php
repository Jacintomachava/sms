<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sender_ids', function (Blueprint $table) {
            $table->id();
            $table->string('sender', 50);
            $table->string('descricao', 255)->nullable();
            /*
            |--------------------------------------------------------------------------
            | TIPO
            |--------------------------------------------------------------------------
            |
            | EXCLUSIVO     -> usado por uma conta específica
            | COMPARTILHADO -> pode ser associado a várias contas
            */
            $table->enum('tipo', ['EXCLUSIVO','COMPARTILHADO'])->default('EXCLUSIVO');
            /*
            |--------------------------------------------------------------------------
            | ESTADO DE APROVAÇÃO
            |--------------------------------------------------------------------------
            */
            $table->enum('estado', ['PENDENTE','EM_APROVACAO_OPERADORA','APROVADO','REJEITADO','SUSPENSO'])->default('PENDENTE');
            $table->foreignId('analisado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('analisado_em')->nullable();
            $table->string('operadora', 50)->nullable();
            $table->timestamp('aprovado_operadora_em')->nullable();
            $table->text('motivo_rejeicao')->nullable();
            $table->text('observacao')->nullable();
            $table->timestamp('aprovado_em')->nullable();
            $table->timestamp('suspenso_em')->nullable();
            $table->timestamps();
            /*
            |--------------------------------------------------------------------------
            | Sender deve existir uma única vez
            |--------------------------------------------------------------------------
            */
            $table->unique('sender');
            $table->index(['tipo','estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sender_ids');
    }
};