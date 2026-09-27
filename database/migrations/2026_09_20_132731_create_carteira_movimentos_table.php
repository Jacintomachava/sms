<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carteira_movimentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carteira_id')->constrained('carteiras')->restrictOnDelete();
            $table->enum('tipo', [ 'CREDITO', 'DEBITO']);
            $table->enum('origem', [
                'COMPRA',
                'SMS',
                'CAMPANHA',
                'AJUSTE',
                'ESTORNO'
            ]);
            /*
            * Quantidade de SMS/segmentos movimentados.
            */
            $table->unsignedBigInteger('quantidade_sms');
            $table->unsignedBigInteger('saldo_anterior');
            $table->unsignedBigInteger('saldo_posterior');
            $table->string('referencia', 100)->nullable();
            $table->string('descricao', 255)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['carteira_id','tipo']);
            $table->index(['carteira_id','origem']);
            $table->index('referencia');
            $table->index('created_at');
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('carteira_movimentos');
    }
};