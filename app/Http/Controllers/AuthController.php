<?php

namespace App\Http\Controllers;

use App\Models\Conta;
use App\Models\ContaUser;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

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

    public function login(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | VALIDAR
        |--------------------------------------------------------------------------
        */
        $dados = $request->validate([
            'email' => ['required','email',],
            'senha' => ['required','string',],
        ]);

        /*
        |--------------------------------------------------------------------------
        | TENTAR AUTENTICAR
        |--------------------------------------------------------------------------
        */
        $autenticado = Auth::attempt([
            'email' => strtolower(trim($dados['email'])),
            'password' => $dados['senha'],
        ], $request->boolean('remember'));

        /*
        |--------------------------------------------------------------------------
        | CREDENCIAIS INCORRECTAS
        |--------------------------------------------------------------------------
        */
        if (!$autenticado) {
            return response()->json([
                'status' => 0,
                'message' => 'Email ou palavra-passe inválidos.',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | UTILIZADOR AUTENTICADO
        |--------------------------------------------------------------------------
        */
        $user = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | VERIFICAR ESTADO DO UTILIZADOR
        |--------------------------------------------------------------------------
        */
        if ($user->estado !== 'ACTIVO') {

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $mensagem = match ($user->estado) {
                'SUSPENSO' => 'A sua conta encontra-se suspensa.',
                'BLOQUEADO' => 'A sua conta encontra-se bloqueada.',
                default => 'A sua conta não está disponível.',
            };

            return response()->json([
                'status' => 0,
                'message' => $mensagem,
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | REGENERAR SESSÃO
        |--------------------------------------------------------------------------
        */
        $request->session()->regenerate();

        /*
        |--------------------------------------------------------------------------
        | VERIFICAR SE É UTILIZADOR DA PLATAFORMA
        |--------------------------------------------------------------------------
        |
        | Procuramos um role PLATFORM associado ao utilizador.
        |
        */
        $platformUser = DB::table('platform_user_roles')
            ->join('roles','roles.id','=','platform_user_roles.role_id')
            ->where('platform_user_roles.user_id',$user->id)
            ->where('roles.scope','PLATFORM')
            ->where('roles.activo',true)
            ->select(['roles.id as role_id','roles.codigo as role_codigo','roles.nome as role_nome',])
            ->first();

        /*
        |--------------------------------------------------------------------------
        | É UTILIZADOR DA PLATAFORMA
        |--------------------------------------------------------------------------
        */
        if ($platformUser) {

            /*
            * Limpar eventual contexto de conta.
            */
            $request->session()->forget(['conta_id','conta_user_id','role_id',]);

            /*
            * Guardar contexto PLATFORM.
            */
            $request->session()->put('contexto','PLATFORM');
            $request->session()->put('platform_role_id',$platformUser->role_id);
            $request->session()->put('platform_role_codigo',$platformUser->role_codigo);

            /*
            * Actualizar último login.
            */
            $user->update([
                'ultimo_login_em' => now(),
            ]);

            /*
            * Enviar para dashboard administrativo.
            */
            return response()->json([
                'status' => 1,
                'message' => 'Login efectuado com sucesso.',
                'redirect' => route('admin.dashboard'),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | NÃO É PLATFORM → PROCURAR CONTA
        |--------------------------------------------------------------------------
        */
        $contaUser = DB::table('conta_user')
            ->join('contas','contas.id','=','conta_user.conta_id')
            ->where('conta_user.user_id', $user->id)
            ->where('conta_user.estado','ACTIVO')
            ->where('contas.estado','ACTIVA')
            ->whereNull('contas.deleted_at')
            ->select([
                'conta_user.id as conta_user_id',
                'conta_user.conta_id',
                'conta_user.role_id',
                'contas.nome as conta_nome',
            ])
            ->first();

        /*
        |--------------------------------------------------------------------------
        | UTILIZADOR SEM ACESSO
        |--------------------------------------------------------------------------
        |
        | Se não é PLATFORM e também não pertence a nenhuma conta activa,
        | não deve entrar no sistema.
        |
        */
        if (!$contaUser) {

            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'status' => 0,
                'message' => 'Não possui acesso a nenhuma conta activa.',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | GUARDAR CONTEXTO ACCOUNT
        |--------------------------------------------------------------------------
        */
        $request->session()->put('contexto','ACCOUNT');
        $request->session()->put('conta_id',$contaUser->conta_id);
        $request->session()->put('conta_user_id',$contaUser->conta_user_id);
        $request->session()->put('role_id',$contaUser->role_id);

        /*
        |--------------------------------------------------------------------------
        | LIMPAR EVENTUAL CONTEXTO PLATFORM
        |--------------------------------------------------------------------------
        */
        $request->session()->forget(['platform_role_id','platform_role_codigo',]);

        /*
        |--------------------------------------------------------------------------
        | ACTUALIZAR ÚLTIMO LOGIN
        |--------------------------------------------------------------------------
        */
        $user->update([
            'ultimo_login_em' => now(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | RESPOSTA CLIENTE
        |--------------------------------------------------------------------------
        */
        return response()->json([
            'status' => 1,
            'message' =>'Login efectuado com sucesso.',
            'redirect' => route('dashboard'),
        ]);
    }

    public function store(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | VALIDAR DADOS
        |--------------------------------------------------------------------------
        */
        $dados = $request->validate([
            'name' => ['required','string','min:3','max:255',],
            'email' => ['required','email','max:255',Rule::unique('users', 'email')->whereNull('deleted_at'),],
            'telefone' => ['required','string','min:9','max:20',],
            'tipo_conta' => ['required',Rule::in(['INDIVIDUAL','EMPRESA']),],
            /*
            |--------------------------------------------------------------------------
            | DADOS EMPRESA
            |--------------------------------------------------------------------------
            */
            'nome_empresa' => ['nullable','required_if:tipo_conta,EMPRESA','string','max:255',],
            'nome_legal' => ['nullable','required_if:tipo_conta,EMPRESA','string','max:255',],
            'nuit' => ['nullable','required_if:tipo_conta,EMPRESA','string','max:20',],
            /*
            |--------------------------------------------------------------------------
            | PASSWORD
            |--------------------------------------------------------------------------
            */
            'password' => ['required','string','min:8','confirmed',],
            /*
            |--------------------------------------------------------------------------
            | TERMOS
            |--------------------------------------------------------------------------
            */
            'termos' => ['required','accepted',],

        ]);


        try {

            /*
            |--------------------------------------------------------------------------
            | TRANSACTION
            |--------------------------------------------------------------------------
            */
            $resultado = DB::transaction(function () use ($dados) {

                /*
                |--------------------------------------------------------------------------
                | 1. CRIAR UTILIZADOR
                |--------------------------------------------------------------------------
                */
                $user = User::create([
                    'name' => trim($dados['name']),
                    'email' => strtolower(trim($dados['email'])),
                    'telefone' => trim($dados['telefone']),
                    /*
                    * Se no Model User tens:
                    *
                    * 'password' => 'hashed'
                    *
                    * não precisas Hash::make().
                    */
                    'password' => $dados['password'],
                    'estado' => 'ACTIVO',
                ]);
                /*
                |--------------------------------------------------------------------------
                | 2. DEFINIR DADOS DA CONTA
                |--------------------------------------------------------------------------
                */
                if ($dados['tipo_conta'] === 'EMPRESA') {

                    /*
                    |--------------------------------------------------------------------------
                    | EMPRESA
                    |--------------------------------------------------------------------------
                    */
                    $nomeConta = trim($dados['nome_empresa']);
                    $nomeLegal = trim($dados['nome_legal']);
                    $nuit = trim($dados['nuit']);
                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | INDIVIDUAL
                    |--------------------------------------------------------------------------
                    */
                    $nomeConta = trim($dados['name']);
                    $nomeLegal = null;
                    $nuit = null;
                }

                /*
                |--------------------------------------------------------------------------
                | 3. CRIAR CONTA
                |--------------------------------------------------------------------------
                */
                $conta = Conta::create([
                    'nome' => $nomeConta,
                    'tipo' => $dados['tipo_conta'],
                    'nome_legal' => $nomeLegal,
                    'nuit' => $nuit,
                    'email' => strtolower(trim($dados['email'])),
                    'telefone' => trim($dados['telefone']),
                    /*
                    |--------------------------------------------------------------------------
                    | TODA CONTA NOVA COMEÇA PRÉ-PAGA
                    |--------------------------------------------------------------------------
                    */
                    'tipo_cobranca' => 'PRE_PAGO',
                    'estado' => 'ACTIVA',
                ]);

                /*
                |--------------------------------------------------------------------------
                | 4. PROCURAR ROLE OWNER
                |--------------------------------------------------------------------------
                */
                $ownerRole = Role::query()->where('codigo','OWNER')->where('scope','ACCOUNT')->where('activo',true)->first();

                /*
                |--------------------------------------------------------------------------
                | SEGURANÇA
                |--------------------------------------------------------------------------
                */
                if (!$ownerRole) {
                    throw new \Exception(
                        'A role OWNER da conta não está configurada.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | 5. ASSOCIAR UTILIZADOR À CONTA
                |--------------------------------------------------------------------------
                */
                DB::table('conta_user')->insert([
                    'conta_id' => $conta->id,
                    'user_id' => $user->id,
                    'role_id' => $ownerRole->id,
                    'estado' => 'ACTIVO',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                /*
                |--------------------------------------------------------------------------
                | RETORNAR DADOS DA TRANSACTION
                |--------------------------------------------------------------------------
                */
                return [
                    'user' => $user,
                    'conta' => $conta,
                ];

            });

            /*
            |--------------------------------------------------------------------------
            | TRANSACTION TERMINOU COM SUCESSO
            |--------------------------------------------------------------------------
            */
            $user = $resultado['user'];
            $conta = $resultado['conta'];

            /*
            |--------------------------------------------------------------------------
            | 6. FAZER LOGIN
            |--------------------------------------------------------------------------
            */
            Auth::login($user);

            /*
            |--------------------------------------------------------------------------
            | 7. REGENERAR SESSÃO
            |--------------------------------------------------------------------------
            */
            $request->session()->regenerate();

            /*
            |--------------------------------------------------------------------------
            | 8. GUARDAR CONTA ACTUAL
            |--------------------------------------------------------------------------
            |
            | Isto será importante porque futuramente o mesmo utilizador
            | poderá pertencer a várias contas.
            |
            */
            $request->session()->put('conta_id', $conta->id);

            /*
            |--------------------------------------------------------------------------
            | 9. ÚLTIMO LOGIN
            |--------------------------------------------------------------------------
            */
            $user->update([
                'ultimo_login_em' => now(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | 10. RESPOSTA PARA AJAX
            |--------------------------------------------------------------------------
            */
            return response()->json([
                'status' => 1,
                'message' => 'Conta criada com sucesso.',
                'redirect' => route('dashboard'),
            ]);


        } catch (\Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | LOG
            |--------------------------------------------------------------------------
            */
            report($e);

            /*
            |--------------------------------------------------------------------------
            | RESPOSTA
            |--------------------------------------------------------------------------
            */
            return response()->json([
                'status' => 0,
                'message' => 'Não foi possível criar a conta. Tente novamente.',
            ], 500);

        }
    }
    

    public function login1(Request $request)
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