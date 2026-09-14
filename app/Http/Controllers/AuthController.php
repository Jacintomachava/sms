<?php

namespace App\Http\Controllers;

use App\Models\Conta;
use App\Models\ContaUser;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{

    public function index()
    {
        return view('login');
    }

    public function registar()
    {
        return view('registar');
    }
    
    public function register(Request $request)
    {

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'telefone' => 'nullable|string|max:20',
            'password' => 'required|min:8|confirmed',
        ]);

        try {
            DB::beginTransaction();

            // Criar utilizador
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'telefone' => $request->telefone,
                'password' => Hash::make($request->password),
                'estado' => 'ACTIVO',
            ]);

            // Criar conta inicial
            $conta = Conta::create([
                'nome' => $request->name,
                'tipo' => 'INDIVIDUAL',
                'email' => $request->email,
                'telefone' => $request->telefone,
                'tipo_cobranca' => 'PRE_PAGO',
                'estado' => 'ACTIVA',
            ]);

            // Buscar role OWNER
            $owner = Role::where('codigo', 'OWNER')->where('scope', 'ACCOUNT')->firstOrFail();

            // Associar utilizador à conta
            ContaUser::create([
                'conta_id' => $conta->id,
                'user_id' => $user->id,
                'role_id' => $owner->id,
                'estado' => 'ACTIVO',
            ]);

            DB::commit();

            return response()->json([
                'status' => 1,
                'message' => 'Conta criada com sucesso.',
                'data' => [
                    'user' => $user,
                    'conta' => $conta,
                ],
            ], 201);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status' => 0,
                'message' => 'Erro ao criar conta.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'Email ou palavra-passe inválidos.',
                ]);
        }

        $request->session()->regenerate();

        $user = Auth::user();

        if ($user->estado !== 'ACTIVO') {

            Auth::logout();

            return back()->withErrors([
                'email' => 'A sua conta encontra-se bloqueada ou suspensa.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Buscar primeira conta activa
        |--------------------------------------------------------------------------
        */
        $contaUser = ContaUser::where('user_id', $user->id)->where('estado', 'ACTIVO')->with('conta')->first();

        if ($contaUser) {
            session([
                'conta_id' => $contaUser->conta_id
            ]);
        }

        $user->update([
            'ultimo_login_em' => now()
        ]);

        return redirect()->intended(route('dashboard'));
    }
}