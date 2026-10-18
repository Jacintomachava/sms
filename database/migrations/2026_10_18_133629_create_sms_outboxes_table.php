<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_outbox', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sms_id')->constrained('sms')->restrictOnDelete();
            $table->enum('estado', [
                'PENDENTE',
                'PUBLICADO',
                'PROCESSADO',
            ])->default('PENDENTE');
            $table->unsignedInteger('tentativas_publicacao')->default(0);
            $table->timestamp('publicado_em')->nullable();
            $table->timestamp('processado_em')->nullable();

            $table->timestamps();

            $table->unique('sms_id');
            $table->index(['estado', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_outbox');
    }
};
