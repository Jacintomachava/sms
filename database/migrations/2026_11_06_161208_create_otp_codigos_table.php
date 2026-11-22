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
        Schema::create('otp_codigos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('conta_id')
                ->constrained('contas');

            $table->foreignId('sms_id')
                ->nullable()
                ->constrained('sms')
                ->nullOnDelete();

            $table->uuid('referencia')->unique();

            $table->string('telefone', 20);
            $table->string('codigo_hash', 255);

            $table->unsignedTinyInteger('tentativas')->default(0);
            $table->unsignedTinyInteger('max_tentativas')->default(5);

            $table->timestamp('expira_em');
            $table->timestamp('verificado_em')->nullable();

            $table->enum('estado', [
                'PENDENTE',
                'VERIFICADO',
                'EXPIRADO',
                'BLOQUEADO',
                'CANCELADO'
            ])->default('PENDENTE');

            $table->timestamps();

            $table->index(['conta_id', 'telefone', 'estado']);
            $table->index('expira_em');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otp_codigos');
    }
};
