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
        // conta_user
        Schema::create('conta_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conta_id')->constrained('contas');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('role_id')->constrained('roles');
            $table->enum('estado', ['ACTIVO','SUSPENSO','REMOVIDO'])->default('ACTIVO');
            $table->timestamp('suspenso_em')->nullable();
            $table->foreignId('suspenso_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('removido_em')->nullable();
            $table->foreignId('removido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['conta_id','user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conta_user');
    }
};
