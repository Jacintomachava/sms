<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

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
}
