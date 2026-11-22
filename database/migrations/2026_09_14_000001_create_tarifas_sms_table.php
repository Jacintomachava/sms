<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarifas_sms', function (Blueprint $table) {
            $table->id();

            /*
             * NULL = tarifa geral
             * preenchido = tarifa exclusiva da conta
             */
            $table->foreignId('conta_id')->nullable()->constrained('contas')->cascadeOnDelete();
            $table->string('nome', 150);
            /*
             * Faixa de quantidade
             */
            $table->unsignedBigInteger('quantidade_minima');
            $table->unsignedBigInteger('quantidade_maxima')->nullable();
            /*
             * Preço por SMS
             *
             * Ex:
             * 1.2500
             * 1.1500
             * 1.0500
             * 0.8000
             */
            $table->decimal('preco_sms', 10, 4);
            /*
             * Controla se pode ser apresentada
             * publicamente ao cliente.
             *
             * Tarifa negociada normalmente será false.
             */
            $table->boolean('publica')->default(true);
            $table->boolean('activo')->default(true);
            /*
             * Permite tarifas temporárias/contratuais.
             */
            $table->date('data_inicio')->nullable();
            $table->date('data_fim')->nullable();
            $table->text('observacao')->nullable();
            $table->timestamps();

            $table->index('conta_id');
            $table->index('activo');
            $table->index(['quantidade_minima','quantidade_maxima']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarifas_sms');
    }
};