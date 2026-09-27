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
        Schema::create('grupos_contactos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conta_id')->constrained('contas')->cascadeOnDelete();
            $table->string('nome', 150);
            $table->string('descricao', 255)->nullable();
            $table->enum('estado', ['ACTIVO','INACTIVO'])->default('ACTIVO');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['conta_id', 'nome'],'grupos_conta_nome_unique');
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grupos_contactos');
    }
};
