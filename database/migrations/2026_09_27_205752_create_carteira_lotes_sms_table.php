<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carteira_lotes_sms', function (Blueprint $table) {
            $table->id();
            /*
            |--------------------------------------------------------------------------
            | CONTA
            |--------------------------------------------------------------------------
            */
            $table->foreignId('conta_id')->constrained('contas')->restrictOnDelete();
            $table->foreignId('compra_sms_id')->constrained('compras_sms')->restrictOnDelete();
            /*
            |--------------------------------------------------------------------------
            | QUANTIDADES
            |--------------------------------------------------------------------------
            |
            | quantidade_sms:
            | quantidade originalmente adquirida.
            |
            | quantidade_disponivel:
            | saldo ainda existente neste lote.
            |
            */
            $table->unsignedBigInteger('quantidade_sms');
            $table->unsignedBigInteger('quantidade_disponivel');
            /*
            |--------------------------------------------------------------------------
            | PREÇO DE VENDA
            |--------------------------------------------------------------------------
            |
            | Snapshot do preço pelo qual este lote
            | foi vendido ao cliente.
            |
            */
            $table->decimal('preco_venda_unitario', 10, 4);
            $table->decimal('valor_total', 15, 2);
            $table->string('moeda', 3)->default('MZN');
            /*
            |--------------------------------------------------------------------------
            | ORIGEM
            |--------------------------------------------------------------------------
            |
            | COMPRA:
            | cliente comprou créditos normalmente.
            |
            | BONUS:
            | créditos promocionais.
            |
            | AJUSTE:
            | crédito administrativo.
            |
            | ESTORNO:
            | reposição proveniente de uma operação anterior.
            |
            */
            $table->enum('origem', [
                'COMPRA',
                'BONUS',
                'AJUSTE',
                'ESTORNO',
            ])->default('COMPRA');
            /*
            |--------------------------------------------------------------------------
            | REFERÊNCIA
            |--------------------------------------------------------------------------
            |
            | Poderá apontar para pagamento, compra,
            | factura, ajuste administrativo etc.
            |
            */
            $table->string('referencia', 100)->nullable();
            /*
            |--------------------------------------------------------------------------
            | ESTADO
            |--------------------------------------------------------------------------
            */
            $table->enum('estado', [
                'ACTIVO',
                'ESGOTADO',
                'CANCELADO',
            ])->default('ACTIVO');
            $table->timestamp('creditado_em')->nullable();
            $table->text('observacao')->nullable();
            $table->foreignId('criado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index([
                'conta_id',
                'estado',
                'created_at',
                'creditado_em'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carteira_lotes_sms');
    }
};