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
        Schema::create('ciclos_consumo_sms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conta_id')->constrained('contas')->cascadeOnDelete();
            /*
            * Período de consumo.
            *
            * Exemplo:
            * 2026-09-01 até 2026-09-30
            */
            $table->date('periodo_inicio');
            $table->date('periodo_fim');
            /*
            * Quantidade de mensagens.
            */
            $table->unsignedBigInteger('quantidade_sms')->default(0);
            /*
            * Quantidade efectivamente tarifável.
            *
            * Uma mensagem longa pode consumir
            * vários segmentos.
            */
            $table->unsignedBigInteger('segmentos')->default(0);
            /*
            * Valor acumulado estimado.
            *
            * Não significa ainda factura emitida.
            */
            $table->decimal('valor_estimado', 15, 2)->default(0);
            $table->foreignId('tarifa_sms_id')->constrained('tarifas_sms');
            $table->decimal('preco_unitario', 10, 4);
            $table->string('moeda', 3)->default('MZN');
            $table->enum('estado', [
                'ABERTO',
                'FECHADO',
                'FACTURADO'
            ])->default('ABERTO');
            $table->timestamp('fechado_em')->nullable();
            $table->timestamps();

            $table->unique([
                'conta_id',
                'periodo_inicio',
                'periodo_fim'
            ]);

            $table->index(['conta_id','estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ciclos_consumo_sms');
    }
};
