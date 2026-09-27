<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conta_limites_sms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conta_id')->constrained('contas')->cascadeOnDelete();
            /*
            * Normalmente MENSAL para POS_PAGO.
            */
            $table->enum('periodo', [
                'DIARIO',
                'SEMANAL',
                'MENSAL'
            ])->default('MENSAL');
            /*
            * Apenas para controlo/planeamento.
            *
            * Ex:
            * cliente espera consumir 100.000 SMS/mês.
            */
            $table->unsignedBigInteger('limite_orcamento_sms')->nullable();
            /*
            |--------------------------------------------------------------------------
            | ALERTA
            |--------------------------------------------------------------------------
            |
            | Ex:
            | avisar quando consumir 70.000 SMS.
            */
            $table->boolean('alerta_activo')->default(false);
            $table->unsignedBigInteger('limite_alerta_sms')->nullable();
            /*
            |--------------------------------------------------------------------------
            | BLOQUEIO
            |--------------------------------------------------------------------------
            |
            | Ex:
            | parar os envios quando atingir 110.000 SMS.
            */
            $table->boolean('bloqueio_activo')->default(false);
            $table->unsignedBigInteger('limite_bloqueio_sms')->nullable();
            $table->boolean('notificar_email')->default(true);
            $table->boolean('notificar_sms')->default(false);
            /*
            * Por default NÃO activamos.
            *
            * Principalmente no POS_PAGO a INFORDATA/cliente
            * decide se quer usar os limites.
            */
            $table->boolean('activo')->default(false);

            $table->timestamps();
            $table->unique('conta_id');
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('conta_limites_sms');
    }
};