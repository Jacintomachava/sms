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
        Schema::create('mensagem', function (Blueprint $table) {
            $table->id();
            $table->string('telefone')->nullable();
            $table->text('descricao'); 
            $table->enum('tipo', ['Recebida','Enviada','Pendente','Inválido'])->nullable()->default('Pendente'); 
            $table->integer('qtd')->default(1);
            $table->decimal('credito',10,2)->default(0); 
            $table->decimal('custo_real',10,2)->default(0);
            $table->enum('canal', ['SMS','WhatsApp'])->nullable()->default('SMS');  
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade')->onUpdate('cascade'); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mensagem');
    }
};
