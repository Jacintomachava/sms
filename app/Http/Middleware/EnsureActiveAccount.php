<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        /*
        |--------------------------------------------------------------------------
        | CONTA GUARDADA NA SESSÃO
        |--------------------------------------------------------------------------
        */
        $contaId = session('conta_id');

        /*
        |--------------------------------------------------------------------------
        | SE NÃO EXISTIR CONTA NA SESSÃO
        |--------------------------------------------------------------------------
        |
        | Isto pode acontecer, por exemplo:
        | - sessão antiga;
        | - utilizador autenticado antes desta implementação;
        | - sessão limpa parcialmente.
        |
        */
        if (!$contaId) {

            $contaUser = DB::table('conta_user')
                ->join('contas','contas.id','=','conta_user.conta_id')
                ->where('conta_user.user_id',$user->id)
                ->where('conta_user.estado','ACTIVO')
                ->where('contas.estado','ACTIVA')
                ->whereNull('contas.deleted_at')
                ->select(['conta_user.id as conta_user_id','conta_user.conta_id','conta_user.role_id',])
                ->first();

            if (!$contaUser) {

                auth()->logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()
                    ->route('login')
                    ->withErrors(['conta' => 'Não existe uma conta activa associada ao utilizador.']);
            }

            /*
            |--------------------------------------------------------------------------
            | RECONSTRUIR CONTEXTO DA CONTA
            |--------------------------------------------------------------------------
            */
            session([
                'conta_id' => $contaUser->conta_id,
                'conta_user_id' => $contaUser->conta_user_id,
                'role_id' => $contaUser->role_id,
            ]);

            return $next($request);
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDAR CONTA DA SESSÃO
        |--------------------------------------------------------------------------
        |
        | Muito importante:
        |
        | Nunca confiamos apenas no conta_id existente na sessão.
        |
        */
        $contaUser = DB::table('conta_user')
            ->join('contas','contas.id','=','conta_user.conta_id')
            ->where('conta_user.user_id',$user->id)
            ->where('conta_user.conta_id',$contaId)
            ->where('conta_user.estado','ACTIVO')
            ->where('contas.estado','ACTIVA')
            ->whereNull('contas.deleted_at')
            ->select([
                'conta_user.id as conta_user_id',
                'conta_user.conta_id',
                'conta_user.role_id',
            ])
            ->first();

        /*
        |--------------------------------------------------------------------------
        | CONTA INVÁLIDA
        |--------------------------------------------------------------------------
        */
        if (!$contaUser) {

            session()->forget([
                'conta_id',
                'conta_user_id',
                'role_id',
            ]);

            return redirect()
                ->route('login')
                ->withErrors(['conta' => 'A conta seleccionada não está disponível.']);
        }

        /*
        |--------------------------------------------------------------------------
        | SINCRONIZAR CONTEXTO
        |--------------------------------------------------------------------------
        */
        session([
            'conta_user_id' => $contaUser->conta_user_id,
            'role_id' => $contaUser->role_id,
        ]);

        return $next($request);
    }
}