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
        Schema::create('sms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conta_id')->constrained('contas');
            $table->foreignId('sender_id')->constrained('sender_ids');
            $table->string('telefone', 15);
            $table->text('mensagem');
            $table->enum('encoding', [
                'GSM7',
                'UNICODE'
            ])->default('GSM7');
            $table->unsignedInteger('caracteres');
            $table->unsignedSmallInteger('segmentos')->default(1);
            $table->enum('finalidade', ['NORMAL','NOTIFICACAO','NOTIFICACAO_INTERNA','CORTESIA_TESTE'])->default('NORMAL');
            /*
            * Quem suporta financeiramente esta SMS.
            */
            $table->enum('custeado_por', ['CLIENTE','INFORDATA'])->default('CLIENTE');
            /*
            * Se entra ou não no consumo
            * facturável do cliente.
            */
            $table->boolean('facturavel_cliente')->default(true);
            $table->enum('origem', ['PAINEL','API','CAMPANHA'])->default('PAINEL');
            $table->enum('estado', [
                'PENDENTE',
                'PROCESSANDO',
                'ENVIADO',
                'ENTREGUE',
                'FALHADO',
                'REJEITADO',
                'EXPIRADO'
            ])->default('PENDENTE');
            $table->string('provider', 50)->nullable();
            $table->string('provider_message_id', 150)->nullable();
            $table->string('provider_codigo', 100)->nullable();
            $table->text('provider_descricao')->nullable();
            $table->timestamp('enviado_em')->nullable();
            $table->timestamp('entregue_em')->nullable();
            $table->timestamp('falhado_em')->nullable();
            $table->foreignId('criado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['conta_id','estado']);
            $table->index(['conta_id','created_at']);
            $table->index('telefone');
            $table->index('provider_message_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms');
    }
};
