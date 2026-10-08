<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_consumos_creditos', function (Blueprint $table) {
                $table->id();
                /*
                |--------------------------------------------------------------------------
                | SMS
                |--------------------------------------------------------------------------
                */
                $table->foreignId('sms_id')->constrained('sms')->restrictOnDelete();
                /*
                |--------------------------------------------------------------------------
                | LOTE COMERCIAL
                |--------------------------------------------------------------------------
                */
                $table->foreignId('carteira_lote_sms_id')->constrained('carteira_lotes_sms')->restrictOnDelete();
                /*
                |--------------------------------------------------------------------------
                | QUANTIDADE CONSUMIDA
                |--------------------------------------------------------------------------
                */
                $table->unsignedInteger('segmentos');
                /*
                |--------------------------------------------------------------------------
                | SNAPSHOT FINANCEIRO
                |--------------------------------------------------------------------------
                */
                $table->decimal('preco_venda_unitario', 10, 4);
                $table->decimal('valor_total', 15, 4);
                $table->timestamps();

                /*
                |--------------------------------------------------------------------------
                | IDEMPOTÊNCIA POR LOTE
                |--------------------------------------------------------------------------
                */
                $table->unique(['sms_id','carteira_lote_sms_id',]);

                $table->index('sms_id');
                $table->index(['carteira_lote_sms_id','created_at',]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_consumos_creditos');
    }
};