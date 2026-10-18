
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_reservas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sms_id')->constrained('sms')->restrictOnDelete();
            $table->foreignId('carteira_lote_sms_id')->nullable()->constrained('carteira_lotes_sms')->restrictOnDelete();
            $table->foreignId('compra_stock_sms_id')->nullable()->constrained('compras_stock_sms')->restrictOnDelete();
            $table->enum('tipo', [
                'CLIENTE',
                'STOCK',
            ]);
            $table->unsignedInteger('quantidade');
            $table->enum('estado', [
                'RESERVADA',
                'CONSUMIDA',
                'LIBERTADA',
            ])->default('RESERVADA');
            $table->timestamps();
            $table->unique([
                'sms_id',
                'carteira_lote_sms_id',
            ], 'sms_reserva_cliente_unique');
            $table->unique([
                'sms_id',
                'compra_stock_sms_id',
            ], 'sms_reserva_stock_unique');

            $table->index([
                'sms_id',
                'tipo',
                'estado',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_reservas');
    }
};
