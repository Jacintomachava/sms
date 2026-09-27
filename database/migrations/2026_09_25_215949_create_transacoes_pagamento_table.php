<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
       Schema::create('transacoes_pagamento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conta_id')->constrained('contas')->restrictOnDelete();
            $table->foreignId('compra_sms_id')->nullable()->constrained('compras_sms')->restrictOnDelete();
            $table->enum('forma_pagamento', [
                'MPESA',
                'EMOLA',
                'TRANSFERENCIA',
                'DEPOSITO',
                'ENTIDADE REFERENCIA'
            ]);
            $table->string('provider', 50)->nullable();
            $table->decimal('valor', 15, 2);
            $table->string('moeda', 3)->default('MZN');
            $table->string('telefone', 20)->nullable();
            /*
            * Referências geradas por nós.
            */
            $table->string('invoice_id', 100)->nullable();
            $table->string('reference_id', 150)->nullable();
            /*
            * Resposta do provider.
            */
            $table->string('provider_transacao_id', 150)->nullable();
            $table->string('provider_codigo', 50)->nullable();
            $table->string('provider_descricao', 255)->nullable();
            $table->enum('estado', [
                'INICIADA',
                'PROCESSANDO',
                'SUCESSO',
                'FALHADA',
                'CANCELADA',
                'EXPIRADA'
            ])->default('INICIADA');
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->text('erro_tecnico')->nullable();
            $table->timestamp('processada_em')->nullable();
            $table->timestamps();

            $table->unique('reference_id');
            $table->index(['compra_sms_id','estado']);
            $table->index(['conta_id','created_at']);
            $table->index(['forma_pagamento','estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transacoes_pagamento');
    }
};