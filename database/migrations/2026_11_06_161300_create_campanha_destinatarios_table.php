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
        Schema::create('campanha_destinatarios', function (Blueprint $table) {
            $table->id();

            $table->foreignId('campanha_id')
                ->constrained('campanhas_sms')
                ->cascadeOnDelete();

            $table->foreignId('contacto_id')
                ->nullable()
                ->constrained('contactos')
                ->nullOnDelete();

            $table->foreignId('sms_id')
                ->nullable()
                ->constrained('sms')
                ->nullOnDelete();

            $table->string('telefone', 20);

            $table->enum('estado', [
                'PENDENTE',
                'PROCESSANDO',
                'REGISTADO',
                'FALHADO',
                'CANCELADO'
            ])->default('PENDENTE');

            $table->text('erro')->nullable();
            $table->timestamp('processado_em')->nullable();

            $table->timestamps();

            $table->unique(['campanha_id', 'telefone']);
            $table->index(['campanha_id', 'estado']);
        });
    }

    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campanha_destinatarios');
    }
};
