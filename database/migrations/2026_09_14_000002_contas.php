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
        // contas
        Schema::create('contas', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->enum('tipo', ['INDIVIDUAL','EMPRESA'])->default('INDIVIDUAL');
            $table->string('nome_legal')->nullable();
            $table->string('nuit')->nullable();
            $table->string('email')->nullable();
            $table->string('telefone')->nullable();
            $table->enum('tipo_cobranca', ['PRE_PAGO','POS_PAGO'])->default('PRE_PAGO');
            $table->enum('estado', ['ACTIVA','SUSPENSA','BLOQUEADA'])->default('ACTIVA');
            $table->timestamps();
            $table->softDeletes();

            $table->index('tipo');
            $table->index('estado');
            $table->index('tipo_cobranca');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contas');
    }
};
