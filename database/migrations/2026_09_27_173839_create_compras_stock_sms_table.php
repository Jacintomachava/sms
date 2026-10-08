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
        Schema::create('compras_stock_sms', function (Blueprint $table) {
            $table->id();
            $table->string('operadora', 50)->default('MOVITEL');
            // Quantidade originalmente comprada
            $table->unsignedBigInteger('quantidade_sms');
            // Quantidade que ainda existe neste lote
            $table->unsignedBigInteger('quantidade_disponivel');
            // Preço realmente pago por SMS/segmento
            $table->decimal('preco_unitario', 10, 4);
            $table->decimal('valor_total', 15, 2);
            $table->string('moeda', 3)->default('MZN');
            $table->boolean('iva_incluido')->default(true);
            // Referência/factura da Movitel
            $table->string('referencia', 100)->nullable();
            $table->date('data_compra');
            $table->string('documento')->nullable();
            $table->text('observacao')->nullable();
            $table->enum('estado', [
                'ACTIVO',
                'ESGOTADO',
                'CANCELADO'
            ])->default('ACTIVO');
            $table->foreignId('criado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Ajuda posteriormente no FIFO
            $table->index([
                'operadora',
                'estado',
                'data_compra'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compras_stock_sms');
    }
};
