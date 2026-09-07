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
            $table->string('nome')->nullable();
            $table->string('username')->nullable()->unique();
            $table->string('email')->unique()->nullable();  
            $table->string('telefone')->unique();
            $table->boolean('estado')->nullable()->default(true);
            $table->string('password')->nullable();
            $table->enum('role', ['Admin','Normal'])->nullable()->default('Admin');
            $table->rememberToken();
            $table->timestamps();
        });

        // INSERE O USUÁRIO PADRÃO ADMIN
        DB::table('users')->insert([
            'nome' => 'Teste Admin',
            'username' => 'admin',
            'email' => 'admin@gmail.com',
            'telefone' => '844870386',
            'estado' => true,
            'password' => Hash::make('123456'),
            'role' => 'Admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
