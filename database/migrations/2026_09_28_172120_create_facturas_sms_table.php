<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facturas_sms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conta_id')->constrained('contas')->restrictOnDelete();
            /*
             * Para POS-PAGO.
             *
             * Nullable porque futuramente podemos querer emitir
             * documentos que não tenham origem num ciclo POS.
             */
            $table->foreignId('ciclo_consumo_sms_id')->nullable()->constrained('ciclos_consumo_sms')->restrictOnDelete();
            /*
             * Número comercial da factura.
             */
            $table->string('numero', 50)->unique();
            /*
             * Período facturado.
             */
            $table->date('periodo_inicio')->nullable();
            $table->date('periodo_fim')->nullable();
            /*
             * Snapshot da quantidade.
             */
            $table->unsignedBigInteger('quantidade_sms')->default(0);
            $table->unsignedBigInteger('segmentos')->default(0);
            /*
             * Snapshot financeiro.
             */
            $table->decimal('preco_unitario', 10, 4);
            $table->decimal('subtotal', 15, 2);
            $table->decimal('valor_total', 15, 2);
            $table->string('moeda', 3)->default('MZN');
            /*
             * Datas.
             */
            $table->date('data_emissao');
            $table->date('data_vencimento')->nullable();
            /*
             * Estado financeiro da factura.
             */
            $table->enum('estado', [
                'PENDENTE',
                'PAGA',
                'VENCIDA',
                'CANCELADA',
            ])->default('PENDENTE');
            $table->timestamp('paga_em')->nullable();
            $table->timestamp('cancelada_em')->nullable();
            /*
             * Auditoria.
             */
            $table->foreignId('criado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cancelado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->text('observacao')->nullable();
            $table->timestamps();
            /*
             * Um ciclo POS só pode originar uma factura.
             */
            $table->unique('ciclo_consumo_sms_id');
            
            $table->index([
                'conta_id',
                'estado',
                'data_emissao',
            ]);

            $table->index('data_vencimento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facturas_sms');
    }
};