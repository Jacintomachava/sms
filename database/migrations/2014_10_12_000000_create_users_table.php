<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('telefone')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('estado', ['ACTIVO','SUSPENSO','BLOQUEADO'])->default('ACTIVO');
            $table->timestamp('ultimo_login_em')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->softDeletes();

            $table->index('estado');
            $table->index('telefone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
