<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conta_id')->constrained('contas')->cascadeOnDelete();
            $table->string('nome', 100)->default('API Principal');
            /*
             * Guardaremos apenas o hash da chave.
             */
            $table->string('key_hash', 64)->unique();
            /*
             * Pequena parte visível para identificação.
             * Ex.: sms_live_ab12...x9k2
             */
            $table->string('prefixo', 30);
            $table->enum('estado', ['ACTIVA','REVOGADA'])->default('ACTIVA');
            $table->timestamp('ultimo_uso_em')->nullable();
            $table->string('ultimo_ip', 45)->nullable();
            $table->foreignId('criado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revogada_em')->nullable();
            $table->foreignId('revogada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['conta_id','estado']);
            
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};