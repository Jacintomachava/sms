<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carteiras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conta_id')->constrained('contas')->restrictOnDelete();
            /*
            * Principalmente PRE_PAGO.
            *
            * Representa créditos SMS disponíveis.
            */
            $table->unsignedBigInteger('saldo_sms')->default(0);
            $table->enum('estado', ['ACTIVA','BLOQUEADA'])->default('ACTIVA');
            /*
            * PRE_PAGO:
            * Ex.: avisar quando restarem 1.000 SMS.
            */
            $table->boolean('alerta_saldo_baixo_activo')->default(false);
            $table->unsignedBigInteger('alerta_saldo_baixo')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('conta_id');
            $table->index('estado');
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('carteiras');
    }
};