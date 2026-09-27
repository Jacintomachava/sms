<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CheckAccountPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response {

        $user = $request->user();

        if (!$user) {
            abort(401);
        }

        $contaId = session('conta_id');

        if (!$contaId) {
            abort(403, 'Nenhuma conta activa seleccionada.');
        }

        /*
        |--------------------------------------------------------------------------
        | BUSCAR CONTA_USER
        |--------------------------------------------------------------------------
        */
        $contaUser = DB::table('conta_user')
            ->where('user_id',$user->id)
            ->where('conta_id',$contaId)
            ->where('estado','ACTIVO')
            ->first();

        if (!$contaUser) {
            abort(403, 'Sem acesso a esta conta.');
        }

        /*
        |--------------------------------------------------------------------------
        | PERMISSÃO ATRAVÉS DA ROLE
        |--------------------------------------------------------------------------
        |
        | conta_user
        |      ↓
        | role
        |      ↓
        | role_permissions
        |      ↓
        | permission
        |
        */
        $temPermissaoRole = DB::table('role_permissions')
            ->join('permissions','permissions.id','=','role_permissions.permission_id')
            ->where('role_permissions.role_id',$contaUser->role_id)
            ->where('permissions.codigo',$permission)
            ->where('permissions.scope','ACCOUNT')
            ->where('permissions.activo',true)
            ->exists();

        if ($temPermissaoRole) {
            return $next($request);
        }

        /*
        |--------------------------------------------------------------------------
        | PERMISSÃO INDIVIDUAL
        |--------------------------------------------------------------------------
        |
        | Para utilizadores PERSONALIZADO ou permissões adicionais.
        |
        */
        $temPermissaoIndividual = DB::table('conta_user_permissions')
            ->join('permissions','permissions.id','=','conta_user_permissions.permission_id')
            ->where('conta_user_permissions.conta_user_id',$contaUser->id)
            ->where('permissions.codigo',$permission)
            ->where('permissions.scope','ACCOUNT')
            ->where('permissions.activo',true)
            ->exists();

        if ($temPermissaoIndividual) {
            return $next($request);
        }

        /*
        |--------------------------------------------------------------------------
        | NÃO TEM PERMISSÃO
        |--------------------------------------------------------------------------
        */
        abort(403,'Não tem permissão para executar esta operação.');
    }
}