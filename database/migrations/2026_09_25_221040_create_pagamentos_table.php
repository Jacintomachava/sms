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
        Schema::create('pagamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conta_id')->constrained('contas')->restrictOnDelete();
            $table->foreignId('compra_sms_id')->nullable()->constrained('compras_sms')->restrictOnDelete();
            /*
            * Qual transação originou este pagamento.
            */
            $table->foreignId('transacao_pagamento_id')->nullable()->constrained('transacoes_pagamento')->restrictOnDelete();
            $table->enum('tipo', ['COMPRA_SMS','FACTURA']);
            $table->enum('forma_pagamento', [
                'MPESA',
                'EMOLA',
                'TRANSFERENCIA',
                'DEPOSITO',
                'ENTIDADE REFERENCIA'
            ]);
            $table->decimal('valor', 15, 2);
            $table->string('moeda', 3)->default('MZN');
            $table->string('referencia', 150)->nullable();
            $table->timestamp('pago_em');
            $table->foreignId('confirmado_por')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            /*
            * Uma transação de sucesso não pode
            * gerar dois pagamentos.
            */
            $table->unique('transacao_pagamento_id');
            $table->unique('compra_sms_id');
            $table->index(['conta_id','pago_em']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagamentos');
    }
};
