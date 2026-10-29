<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factura_pagamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factura_sms_id')->constrained('facturas_sms')->restrictOnDelete();
            $table->foreignId('pagamento_id')->constrained('pagamentos')->restrictOnDelete();
            $table->decimal('valor_aplicado', 15, 2);
            $table->timestamps();

            $table->unique(
                ['factura_sms_id', 'pagamento_id'],
                'factura_pagamentos_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factura_pagamentos');
    }
};