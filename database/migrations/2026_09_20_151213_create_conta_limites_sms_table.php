<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
       Schema::create('conta_limites_sms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conta_id')->constrained('contas')->cascadeOnDelete();
            $table->enum('periodo', [
                'DIARIO',
                'MENSAL'
            ])->default('MENSAL');
            $table->unsignedBigInteger('limite_sms');
            $table->boolean('alertas_activos')->default(true);
            $table->json('percentuais_alerta')->nullable();
            $table->boolean('bloqueio_activo')->default(false);
            $table->unsignedSmallInteger('corte_percentual')->nullable();
            /*
            |--------------------------------------------------------------------------
            | DESTINATÁRIO DOS ALERTAS
            |--------------------------------------------------------------------------
            |
            | Não são obrigatórios.
            |
            | Exemplo:
            | nome     = Departamento Financeiro
            | email    = financeiro@empresa.co.mz
            | telefone = 25884XXXXXXX
            |
            */
            $table->string('nome_notificacao', 150)->nullable();
            $table->string('email_notificacao', 150)->nullable();
            $table->string('telefone_notificacao', 20)->nullable();
            $table->boolean('notificar_email')->default(false);
            $table->boolean('notificar_sms')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique([
                'conta_id',
                'periodo'
            ]);
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('conta_limites_sms');
    }
};