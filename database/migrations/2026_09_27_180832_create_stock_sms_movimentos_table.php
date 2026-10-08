<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_sms_movimentos', function (Blueprint $table) {
            $table->id();
            /*
             * Lote de stock Movitel afectado.
             */
            $table->foreignId('compra_stock_sms_id')->constrained('compras_stock_sms')->restrictOnDelete();
            /*
             * ENTRADA:
             * compra de stock à Movitel.
             *
             * CONSUMO:
             * SMS efectivamente consumida.
             *
             * AJUSTE_ENTRADA/AJUSTE_SAIDA:
             * correcções administrativas.
             *
             * ESTORNO:
             * devolução de stock anteriormente consumido.
             */
            $table->enum('tipo', [
                'ENTRADA',
                'CONSUMO',
                'AJUSTE_ENTRADA',
                'AJUSTE_SAIDA',
                'ESTORNO',
            ]);
            $table->unsignedBigInteger('quantidade_sms');
            /*
             * Saldo deste lote antes/depois do movimento.
             */
            $table->unsignedBigInteger('saldo_anterior');
            $table->unsignedBigInteger('saldo_posterior');
            /*
             * Ex:
             * COMPRA-15
             * SMS-829
             * AJUSTE-2026-001
             */
            $table->string('referencia', 100)->nullable();
            $table->string('descricao', 255)->nullable();
            /*
             * Informações adicionais sem necessidade
             * de alterar a estrutura da tabela.
             */
            $table->json('metadata')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index([
                'compra_stock_sms_id',
                'created_at'
            ]);

            $table->index([
                'tipo',
                'created_at'
            ]);

            $table->index('referencia');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_sms_movimentos');
    }
};