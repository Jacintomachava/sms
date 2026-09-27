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
        Schema::create('conta_limite_notificacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conta_id')->constrained('contas')->cascadeOnDelete();
            $table->enum('tipo', [
                'SALDO_BAIXO',
                'LIMITE_ALERTA',
                'ORCAMENTO',
                'LIMITE_BLOQUEIO'
            ]);
            /*
            * Exemplos:
            *
            * 2026-09
            * 2026-W38
            * 2026-09-25
            */
            $table->string('periodo', 20);
            /*
            * Tudo em SMS.
            */
            $table->unsignedBigInteger('quantidade_limite')->nullable();
            $table->unsignedBigInteger('quantidade_actual')->nullable();
            $table->enum('canal', ['EMAIL','SMS']);
            $table->timestamp('enviado_em');
            $table->timestamps();

            $table->index(['conta_id','tipo','periodo']);

            $table->unique(
                [
                    'conta_id',
                    'tipo',
                    'periodo',
                    'canal'
                ],
                'uq_conta_limite_notificacao'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conta_limite_notificacoes');
    }
};
