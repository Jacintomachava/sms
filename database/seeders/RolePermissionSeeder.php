<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | ADMIN DA CONTA
        |--------------------------------------------------------------------------
        |
        | O ADMIN recebe todas as permissões ACCOUNT.
        |
        */
        $accountAdmin = Role::where('codigo', 'ADMIN')->where('scope', 'ACCOUNT')->firstOrFail();

        $accountPermissions = Permission::where('scope', 'ACCOUNT')->pluck('id');

        $accountAdmin->permissions()->sync($accountPermissions);

        /*
        |--------------------------------------------------------------------------
        | OWNER
        |--------------------------------------------------------------------------
        |
        | Também associamos todas as permissões.
        | Mais tarde o OWNER terá protecções adicionais no código.
        |
        */
        $owner = Role::where('codigo', 'OWNER')->where('scope', 'ACCOUNT')->firstOrFail();

        $owner->permissions()->sync($accountPermissions);

        /*
        |--------------------------------------------------------------------------
        | ADMIN DA PLATAFORMA
        |--------------------------------------------------------------------------
        */
        $platformAdmin = Role::where('codigo', 'ADMIN')->where('scope', 'PLATFORM')->firstOrFail();

        $platformPermissions = Permission::where('scope', 'PLATFORM')->pluck('id');

        $platformAdmin->permissions()->sync($platformPermissions);

        /*
        |--------------------------------------------------------------------------
        | SUPER ADMIN
        |--------------------------------------------------------------------------
        */
        $superAdmin = Role::where('codigo', 'SUPER_ADMIN')->where('scope', 'PLATFORM')->firstOrFail();

        $superAdmin->permissions()->sync($platformPermissions);

        /*
        |--------------------------------------------------------------------------
        | PERSONALIZADO
        |--------------------------------------------------------------------------
        |
        | Não associamos permissões automaticamente.
        | Serão atribuídas individualmente.
        |
        */
    }
}