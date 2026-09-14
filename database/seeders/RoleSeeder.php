<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | ROLES DAS CONTAS DOS CLIENTES
        |--------------------------------------------------------------------------
        */
        Role::updateOrCreate(
            [
                'codigo' => 'OWNER',
                'scope' => 'ACCOUNT',
            ],
            [
                'nome' => 'Proprietário',
                'descricao' => 'Proprietário da conta com acesso total.',
                'activo' => true,
            ]
        );

        Role::updateOrCreate(
            [
                'codigo' => 'ADMIN',
                'scope' => 'ACCOUNT',
            ],
            [
                'nome' => 'Administrador',
                'descricao' => 'Administrador da conta.',
                'activo' => true,
            ]
        );

        Role::updateOrCreate(
            [
                'codigo' => 'PERSONALIZADO',
                'scope' => 'ACCOUNT',
            ],
            [
                'nome' => 'Personalizado',
                'descricao' => 'Utilizador com permissões personalizadas.',
                'activo' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | ROLES DA ADMINISTRAÇÃO DA PLATAFORMA
        |--------------------------------------------------------------------------
        */
        Role::updateOrCreate(
            [
                'codigo' => 'SUPER_ADMIN',
                'scope' => 'PLATFORM',
            ],
            [
                'nome' => 'Super Administrador',
                'descricao' => 'Acesso total à administração da plataforma.',
                'activo' => true,
            ]
        );

        Role::updateOrCreate(
            [
                'codigo' => 'ADMIN',
                'scope' => 'PLATFORM',
            ],
            [
                'nome' => 'Administrador',
                'descricao' => 'Administrador da plataforma.',
                'activo' => true,
            ]
        );

        Role::updateOrCreate(
            [
                'codigo' => 'PERSONALIZADO',
                'scope' => 'PLATFORM',
            ],
            [
                'nome' => 'Personalizado',
                'descricao' => 'Utilizador da plataforma com permissões personalizadas.',
                'activo' => true,
            ]
        );
    }
}