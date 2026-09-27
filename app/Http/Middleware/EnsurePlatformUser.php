<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformUser
{
    public function handle(Request $request, Closure $next): Response {

        /*
        |--------------------------------------------------------------------------
        | UTILIZADOR AUTENTICADO
        |--------------------------------------------------------------------------
        */
        $user = $request->user();

        if (!$user) {

            if ($request->expectsJson()) {

                return response()->json([
                    'status' => 0,
                    'message' => 'Não autenticado.',
                ], 401);
            }

            return redirect()->route('login');
        }

        /*
        |--------------------------------------------------------------------------
        | UTILIZADOR ACTIVO
        |--------------------------------------------------------------------------
        */
        if ($user->estado !== 'ACTIVO') {

            abort(403, 'O utilizador não está activo.');
        }

        /*
        |--------------------------------------------------------------------------
        | VERIFICAR ROLE PLATFORM
        |--------------------------------------------------------------------------
        |
        | Não confiamos apenas em:
        |
        | session('contexto') === 'PLATFORM'
        |
        | A base de dados continua a ser a fonte de verdade.
        |
        */
        $platformRole = DB::table('platform_user_roles')

            ->join('roles','roles.id','=','platform_user_roles.role_id')
            ->where('platform_user_roles.user_id',$user->id)
            ->where('roles.scope','PLATFORM')
            ->where('roles.activo',true)
            ->select([
                'roles.id',
                'roles.codigo',
                'roles.nome',
            ])
            ->first();

        /*
        |--------------------------------------------------------------------------
        | NÃO É UTILIZADOR PLATFORM
        |--------------------------------------------------------------------------
        */
        if (!$platformRole) {

            if ($request->expectsJson()) {

                return response()->json([
                    'status' => 0,
                    'message' => 'Não possui acesso administrativo à plataforma.',
                ], 403);
            }

            abort(403,'Não possui acesso administrativo à plataforma.');
        }

        /*
        |--------------------------------------------------------------------------
        | GARANTIR CONTEXTO PLATFORM
        |--------------------------------------------------------------------------
        */
        $request->session()->put('contexto','PLATFORM');
        $request->session()->put('platform_role_id', $platformRole->id);
        $request->session()->put('platform_role_codigo', $platformRole->codigo);

        /*
        |--------------------------------------------------------------------------
        | CONTINUAR
        |--------------------------------------------------------------------------
        */
        return $next($request);
    }
}