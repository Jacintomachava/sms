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
       Schema::create('smpp_tentativas_envio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sms_id')->constrained('sms')->restrictOnDelete();
            $table->string('host', 45);
            $table->unsignedSmallInteger('porta');
            $table->string('worker_id', 100);
            $table->uuid('session_id')->nullable();
            $table->unsignedInteger('sequence_number')->nullable();
            $table->enum('estado', [
                'PREPARADA',
                'SUBMETENDO',
                'ACEITE',
                'REJEITADA',
                'INCERTA',
                ])->default('PREPARADA');
            $table->string('provider_message_id', 150)->nullable();
            $table->unsignedInteger('command_status')->nullable();
            $table->text('erro')->nullable();
            $table->timestamp('iniciada_em')->nullable();
            $table->timestamp('respondida_em')->nullable();
            $table->timestamps();

            $table->index(['sms_id', 'estado']);
            $table->index(['estado', 'created_at']);

            $table->index([
                'worker_id',
                'sequence_number',
            ]);

            $table->index([
                'provider_message_id',
            ]);

            $table->unique(
                ['session_id', 'sequence_number'],
                'smpp_tentativas_sessao_seq_unique'
            );
            
       });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('smpp_tentativas_envio');
    }
};
