<?php

namespace App\Http\Controllers;

use App\Models\Conta;
use App\Models\ContaUser;
use App\Models\Role;
use App\Models\User;
use App\Services\CriarContaService;
use App\Services\SenderPadraoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{

    private CriarContaService $criarContaService;

    public function __construct(CriarContaService $criarContaService) {
        
        $this->criarContaService = $criarContaService;
    }

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
                'redirect' => route('admin.dashboard.home'),
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

                // IMPORTANTE
                'contas.tipo_cobranca as tipo_cobranca',

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
        $request->session()->put('tipo_cobranca', $contaUser->tipo_cobranca);

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
            'redirect' => route('financeiro.index'),
        ]);
    }

    public function store(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | 1. VALIDAR DADOS
        |--------------------------------------------------------------------------
        */
        $dados = $request->validate([
            'name' => [
                'required',
                'string',
                'min:3',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->whereNull('deleted_at'),
            ],

            'telefone' => [
                'required',
                'string',
                'min:9',
                'max:20',
            ],

            'tipo_conta' => [
                'required',
                Rule::in(['INDIVIDUAL', 'EMPRESA']),
            ],

            /*
            |--------------------------------------------------------------------------
            | DADOS EMPRESA
            |--------------------------------------------------------------------------
            */
            'nome_empresa' => [
                'nullable',
                'required_if:tipo_conta,EMPRESA',
                'string',
                'max:255',
            ],

            'nome_legal' => [
                'nullable',
                'required_if:tipo_conta,EMPRESA',
                'string',
                'max:255',
            ],

            'nuit' => [
                'nullable',
                'required_if:tipo_conta,EMPRESA',
                'string',
                'max:20',
            ],

            /*
            |--------------------------------------------------------------------------
            | PASSWORD
            |--------------------------------------------------------------------------
            */
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],

            /*
            |--------------------------------------------------------------------------
            | TERMOS
            |--------------------------------------------------------------------------
            */
            'termos' => [
                'required',
                'accepted',
            ],
        ]);

        try {

            /*
            |--------------------------------------------------------------------------
            | 2. CRIAR UTILIZADOR E CONTA
            |--------------------------------------------------------------------------
            |
            | O CriarContaService já cria:
            | - Utilizador
            | - Conta PRE_PAGO
            | - Associação OWNER
            | - Sender partilhado InfoSMS
            |
            */
            $resultado = DB::transaction(function () use ($dados) {

                return $this->criarContaService->criar(
                    dados: $dados,
                    tipoCobranca: 'PRE_PAGO'
                );

            });

            /*
            |--------------------------------------------------------------------------
            | 3. OBTER UTILIZADOR E CONTA
            |--------------------------------------------------------------------------
            */
            $user = $resultado['user'];
            $conta = $resultado['conta'];

            /*
            |--------------------------------------------------------------------------
            | 4. LOCALIZAR ASSOCIAÇÃO OWNER
            |--------------------------------------------------------------------------
            |
            | Esta associação já foi criada pelo CriarContaService.
            | Vamos recuperar o role_id e o conta_user_id.
            |
            */
            $contaUser = DB::table('conta_user')
                ->where('conta_id', $conta->id)
                ->where('user_id', $user->id)
                ->where('estado', 'ACTIVO')
                ->first();

            if (!$contaUser) {
                throw new \RuntimeException(
                    'Não foi possível localizar a associação OWNER da conta.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 5. FAZER LOGIN AUTOMÁTICO
            |--------------------------------------------------------------------------
            */
            Auth::login($user);

            /*
            |--------------------------------------------------------------------------
            | 6. REGENERAR SESSÃO
            |--------------------------------------------------------------------------
            */
            $request->session()->regenerate();

            /*
            |--------------------------------------------------------------------------
            | 7. GUARDAR CONTEXTO ACCOUNT
            |--------------------------------------------------------------------------
            |
            | IMPORTANTE:
            | Estes dados são utilizados pelo sistema para identificar
            | a conta activa, a role e as permissões do utilizador.
            |
            */
            $request->session()->put([
                'contexto' => 'ACCOUNT',
                'conta_id' => $conta->id,
                'conta_user_id' => $contaUser->id,
                'role_id' => $contaUser->role_id,
                'tipo_cobranca' => $conta->tipo_cobranca,
            ]);

            /*
            |--------------------------------------------------------------------------
            | 8. LIMPAR CONTEXTO DA PLATAFORMA
            |--------------------------------------------------------------------------
            */
            $request->session()->forget([
                'platform_role_id',
                'platform_role_codigo',
            ]);

            /*
            |--------------------------------------------------------------------------
            | 9. ACTUALIZAR ÚLTIMO LOGIN
            |--------------------------------------------------------------------------
            */
            $user->update([
                'ultimo_login_em' => now(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | 10. RESPOSTA AJAX
            |--------------------------------------------------------------------------
            */
            return response()->json([
                'status' => 1,
                'message' => 'Conta criada com sucesso.',
                'redirect' => route('financeiro.index'),
            ]);

        } catch (\Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | REGISTAR ERRO
            |--------------------------------------------------------------------------
            */
            report($e);

            /*
            |--------------------------------------------------------------------------
            | RESPOSTA DE ERRO
            |--------------------------------------------------------------------------
            */
            return response()->json([
                'status' => 0,
                'message' => 'Não foi possível concluir o registo. Tente novamente.',
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