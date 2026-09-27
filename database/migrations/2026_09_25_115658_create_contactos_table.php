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
        Schema::create('contactos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conta_id')->constrained('contas')->cascadeOnDelete();
            $table->string('nome', 150);
            $table->string('telefone', 20);
            $table->string('email', 150)->nullable();
            $table->date('data_nascimento')->nullable();
            $table->enum('estado', ['ACTIVO','INACTIVO'])->default('ACTIVO');
            $table->timestamps();
            $table->softDeletes();
            // Um número não deve ser repetido dentro da mesma conta
            $table->unique(['conta_id', 'telefone'],'contactos_conta_telefone_unique');
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contactos');
    }
};
