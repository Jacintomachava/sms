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
        Schema::create('campanhas_sms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conta_id')->constrained('contas');
            $table->foreignId('grupo_contacto_id')->constrained('grupos_contactos');
            $table->foreignId('sender_id')->constrained('sender_ids');
            $table->foreignId('criado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('nome', 150);
            $table->text('mensagem');
            $table->enum('estado', [
                'RASCUNHO',
                'PENDENTE',
                'PROCESSANDO',
                'CONCLUIDA',
                'CANCELADA',
                'FALHADA'
            ])->default('RASCUNHO');
            $table->unsignedInteger('total_destinatarios')->default(0);
            $table->unsignedInteger('total_processados')->default(0);
            $table->unsignedInteger('total_falhados')->default(0);
            $table->unsignedInteger('total_cancelados')->default(0);
            $table->timestamp('iniciada_em')->nullable();
            $table->timestamp('concluida_em')->nullable();
            $table->timestamp('cancelada_em')->nullable();
            $table->foreignId('cancelada_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('motivo_cancelamento', 255)->nullable();
            $table->timestamps();

            $table->index(['conta_id', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campanhas_sms');
    }
};
