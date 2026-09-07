<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // Se a requisição for de API, retorna JSON 401 (não autenticado)
    if ($request->expectsJson()) {
        abort(response()->json([
            'message' => 'Usuário não autenticado. Faça login ou verifique o OTP.'
        ], 401));
    }
    //se a requisicao for da a web retorna null 
        return null;
    }
}
