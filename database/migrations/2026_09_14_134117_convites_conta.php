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
        // convites_conta
        Schema::create('convites_conta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conta_id')->constrained('contas');
            $table->string('email');
            $table->foreignId('convidado_por')->constrained('users');
            $table->foreignId('role_id')->constrained('roles');
            $table->string('token_hash', 64)->unique();
            $table->enum('estado', ['PENDENTE','ACEITE','RECUSADO','EXPIRADO','CANCELADO'])->default('PENDENTE');
            $table->timestamp('expira_em');
            $table->timestamp('aceite_em')->nullable();
            $table->timestamp('recusado_em')->nullable();
            $table->timestamp('cancelado_em')->nullable();
            $table->timestamps();

            $table->index(['conta_id','email','estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('convites_conta');
    }
};
