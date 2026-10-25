<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('smpp_segmentos_envio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('smpp_tentativa_envio_id')->constrained('smpp_tentativas_envio');
            $table->unsignedTinyInteger('numero_segmento');
            $table->unsignedTinyInteger('total_segmentos');
            $table->unsignedTinyInteger('udh_referencia')->nullable();
            $table->uuid('session_id')->nullable();
            $table->unsignedInteger('sequence_number')->nullable();
            $table->string('provider_message_id', 150)->nullable()->index();
            $table->string('estado', 24)->default('PREPARADO');
            $table->unsignedInteger('command_status')->nullable();
            $table->string('dlr_estado', 30)->nullable();
            $table->timestamp('submetido_em')->nullable();
            $table->timestamp('respondido_em')->nullable();
            $table->timestamp('dlr_recebido_em')->nullable();
            $table->timestamps();
            $table->unique(['smpp_tentativa_envio_id', 'numero_segmento'], 'smpp_seg_tentativa_numero_uniq');
            $table->unique(['session_id', 'sequence_number'], 'smpp_seg_sessao_seq_uniq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smpp_segmentos_envio');
    }
};
