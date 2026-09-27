<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PlatformAdminSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            /*
            |--------------------------------------------------------------------------
            | CRIAR UTILIZADOR DA PLATAFORMA
            |--------------------------------------------------------------------------
            */
            $user = User::updateOrCreate(
                [
                    'email' => 'admin@infordata.co.mz',
                ],
                [
                    'name' => 'Administrador INFORDATA',
                    'telefone' => '840000000',
                    'password' => Hash::make('123456789'),
                    'estado' => 'ACTIVO',
                    'email_verified_at' => now(),
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | BUSCAR ROLE SUPER ADMIN
            |--------------------------------------------------------------------------
            */
            $role = Role::query()
                ->where('codigo', 'SUPER_ADMIN')
                ->where('scope', 'PLATFORM')
                ->firstOrFail();

            /*
            |--------------------------------------------------------------------------
            | ATRIBUIR ROLE PLATFORM
            |--------------------------------------------------------------------------
            */
            DB::table('platform_user_roles')
                ->updateOrInsert(
                    [
                        'user_id' => $user->id,
                        'role_id' => $role->id,
                    ],
                    [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

            /*
            |--------------------------------------------------------------------------
            | BUSCAR TODAS AS PERMISSÕES PLATFORM
            |--------------------------------------------------------------------------
            */
            $permissions = Permission::query()->where('scope', 'PLATFORM')->where('activo', true)->get();

            /*
            |--------------------------------------------------------------------------
            | ATRIBUIR TODAS AS PERMISSÕES AO UTILIZADOR
            |--------------------------------------------------------------------------
            */
            foreach ($permissions as $permission) {

                DB::table('platform_user_permissions')
                    ->updateOrInsert(
                        [
                            'user_id' => $user->id,
                            'permission_id' => $permission->id,
                        ],
                        [
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
            }

            $this->command?->info(
                'Utilizador administrador da plataforma criado com sucesso.'
            );

            $this->command?->info(
                'Permissões PLATFORM atribuídas: ' .
                $permissions->count()
            );
        });
    }
}