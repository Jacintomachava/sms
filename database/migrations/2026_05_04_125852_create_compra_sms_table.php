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
        Schema::create('compra_sms', function (Blueprint $table) {
            $table->id();
            $table->string('sistema')->nullable();  //Escola, Agua, etc
            $table->string('sender')->nullable();  
            $table->decimal('saldo_sms',10,2)->default(0);  
            $table->boolean('estado')->nullable()->default(true);  //Estado Activo 
            $table->string('representante')->nullable();  
            $table->string('telefone')->nullable();
            $table->string('cargo')->nullable();  
            $table->enum('tipo', ['pago','gratuito'])->nullable()->default('pago');
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade')->onUpdate('cascade'); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compra_sms');
    }
};
