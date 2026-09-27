<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([

            /*
            |--------------------------------------------------------------------------
            | ESTRUTURA DE PERMISSÕES
            |--------------------------------------------------------------------------
            */
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
            /*
            |--------------------------------------------------------------------------
            | ADMINISTRADOR DA PLATAFORMA
            |--------------------------------------------------------------------------
            */
            PlatformAdminSeeder::class,

            TarifaSmsSeeder::class,

        ]);
    }
}
