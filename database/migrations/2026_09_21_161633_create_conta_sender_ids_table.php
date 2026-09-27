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
        Schema::create('conta_sender_ids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conta_id')->constrained('contas')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('sender_ids')->cascadeOnDelete();
            /*
            |--------------------------------------------------------------------------
            | ESTADO DA ASSOCIAÇÃO
            |--------------------------------------------------------------------------
            |
            | Um Sender pode continuar APROVADO globalmente,
            | mas ser retirado de uma determinada conta.
            */
            $table->enum('estado', ['ACTIVO','SUSPENSO'])->default('ACTIVO');
            $table->foreignId('atribuido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('atribuido_em')->nullable();
            $table->timestamps();
            $table->unique(['conta_id','sender_id']);
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conta_sender_ids');
    }
};
