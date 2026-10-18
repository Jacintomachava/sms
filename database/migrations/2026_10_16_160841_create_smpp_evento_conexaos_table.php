
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smpp_eventos_conexao', function (Blueprint $table) {
            $table->id();
            $table->string('host', 45);
            $table->unsignedSmallInteger('porta')->default(8014);
            $table->enum('tipo', [
                'CONECTADO',
                'FALHA',
                'DESCONECTADO',
                'FAILOVER',
                'RECUPERADO',
                'TODOS_INDISPONIVEIS',
            ]);
            $table->string('host_anterior', 45)->nullable();
            $table->string('host_novo', 45)->nullable();
            $table->string('codigo_erro', 50)->nullable();
            $table->text('descricao')->nullable();
            $table->string('worker_id', 100)->nullable();
            $table->timestamp('ocorrido_em')->useCurrent();
            $table->timestamps();

            $table->index(['host', 'ocorrido_em']);
            $table->index(['tipo', 'ocorrido_em']);
            $table->index(['worker_id', 'ocorrido_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smpp_eventos_conexao');
    }
};
