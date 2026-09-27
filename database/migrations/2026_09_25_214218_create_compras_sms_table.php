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
        Schema::create('compras_sms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conta_id')->constrained('contas')->restrictOnDelete();
            $table->foreignId('tarifa_sms_id')->constrained('tarifas_sms')->restrictOnDelete();
            $table->unsignedBigInteger('quantidade_sms');
            /*
            * Snapshot do preço.
            */
            $table->decimal('preco_unitario', 10, 4);
            $table->decimal('valor_total', 15, 2);
            $table->string('moeda', 3)->default('MZN');
            $table->enum('estado', [
                'PENDENTE',
                'PROCESSANDO',
                'PAGA',
                'FALHADA',
                'CANCELADA',
                'EXPIRADA'
            ])->default('PENDENTE');
            /*
            * Quando os SMS foram efectivamente
            * adicionados à carteira.
            */
            $table->timestamp('creditada_em')->nullable();
            $table->foreignId('criado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['conta_id','estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compras_sms');
    }
};
