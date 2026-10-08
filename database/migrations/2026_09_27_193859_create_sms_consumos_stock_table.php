<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_consumos_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sms_id')->constrained('sms')->restrictOnDelete();
            $table->foreignId('compra_stock_sms_id')->constrained('compras_stock_sms')->restrictOnDelete();
            $table->unsignedInteger('segmentos');
            $table->decimal('preco_compra_unitario', 10, 4);
            $table->decimal('custo_total', 15, 4);
            $table->timestamps();
            $table->unique(['sms_id','compra_stock_sms_id']);

            $table->index('sms_id');
            $table->index(['compra_stock_sms_id','created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_consumos_stock');
    }
};