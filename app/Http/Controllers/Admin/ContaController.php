<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CriarContaService;
use App\Models\Conta;
use App\Models\TarifaSms;
use App\Models\ContaLimiteInternoSms;
use App\Models\ContaLimiteSms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;


class ContaController extends Controller
{

    private CriarContaService $criarContaService;

    public function __construct(CriarContaService $criarContaService) {
        $this->criarContaService = $criarContaService;
    }

    public function index()
    {
        $contas = Conta::query()
            ->with([
                'users',
            ])
            ->latest()
            ->get();

        $tarifas = TarifaSms::query()
            ->where('activo', true)
            ->orderBy('nome')
            ->get();

        return view('admin.contas.index', compact(
            'contas',
            'tarifas'
        ));
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            /*
            |--------------------------------------------------------------------------
            | CONTA
            |--------------------------------------------------------------------------
            */
            'tipo_conta' => ['required', Rule::in(['INDIVIDUAL', 'EMPRESA']),],
            'tipo_cobranca' => ['required', Rule::in(['PRE_PAGO', 'POS_PAGO']),],
            /*
            |--------------------------------------------------------------------------
            | RESPONSÁVEL
            |--------------------------------------------------------------------------
            */
            'name' => ['required','string','min:3','max:255',],
            'email' => ['required','email','max:255', Rule::unique('users', 'email')->whereNull('deleted_at'),],
            'telefone' => ['required','string','min:9','max:20',],
            'password' => ['required','string','min:8','confirmed',],
            /*
            |--------------------------------------------------------------------------
            | EMPRESA
            |--------------------------------------------------------------------------
            */
            'nome_empresa' => ['nullable','required_if:tipo_conta,EMPRESA','string','max:255',],
            'nome_legal' => ['nullable','required_if:tipo_conta,EMPRESA','string','max:255',],
            'nuit' => ['nullable','required_if:tipo_conta,EMPRESA','string','max:20',],
            /*
            |--------------------------------------------------------------------------
            | POS-PAGO
            |--------------------------------------------------------------------------
            */
            'tarifa_sms_id' => ['nullable','required_if:tipo_cobranca,POS_PAGO','integer','exists:tarifas_sms,id',],
            'limite_interno_sms' => ['nullable','required_if:tipo_cobranca,POS_PAGO','integer','min:1',],
            /*
            |--------------------------------------------------------------------------
            | LIMITE DO CLIENTE - OPCIONAL
            |--------------------------------------------------------------------------
            */
            'limite_cliente_sms' => ['nullable','integer','min:1',],
            'corte_percentual' => ['nullable','integer','min:100',],
            'bloqueio_activo' => ['nullable','boolean',],
        ]);

        try {

            $resultado = DB::transaction(function () use ($dados) {

                /*
                |--------------------------------------------------------------------------
                | USER + CONTA + OWNER
                |--------------------------------------------------------------------------
                */
                $resultado = $this->criarContaService->criar(
                    dados: $dados,
                    tipoCobranca: $dados['tipo_cobranca']
                );

                $conta = $resultado['conta'];

                /*
                |--------------------------------------------------------------------------
                | PRE-PAGO
                |--------------------------------------------------------------------------
                |
                | Não possui configuração POS.
                */
                if ($conta->tipo_cobranca === 'PRE_PAGO') {
                    return $resultado;
                }

                /*
                |--------------------------------------------------------------------------
                | POS-PAGO - TARIFA
                |--------------------------------------------------------------------------
                */
                $conta->update([
                    'tarifa_sms_id' => $dados['tarifa_sms_id'],
                ]);

                /*
                |--------------------------------------------------------------------------
                | LIMITE INTERNO INFORDATA
                |--------------------------------------------------------------------------
                |
                | Obrigatório para qualquer conta POS-PAGO.
                */
                ContaLimiteInternoSms::create([
                    'conta_id' => $conta->id,
                    'limite_sms' => $dados['limite_interno_sms'],
                    'periodo' => 'MENSAL',
                    'activo' => true,
                    'criado_por' => auth()->id(),
                    'alterado_por' => null,
                ]);

                /*
                |--------------------------------------------------------------------------
                | LIMITE DO CLIENTE
                |--------------------------------------------------------------------------
                |
                | Opcional.
                */
                if (!empty($dados['limite_cliente_sms'])) {

                    ContaLimiteSms::create([
                        'conta_id' => $conta->id,
                        'periodo' => 'MENSAL',
                        'limite_sms' => $dados['limite_cliente_sms'],
                        /*
                        | Alertas serão configurados posteriormente.
                        */
                        'alertas_activos' => false,
                        'percentuais_alerta' => null,
                        /*
                        | Corte opcional.
                        */
                        'bloqueio_activo' => !empty($dados['bloqueio_activo']),
                        'corte_percentual' => !empty($dados['bloqueio_activo']) ? ($dados['corte_percentual'] ?? 100) : null,
                        /*
                        | Notificações serão configuradas posteriormente.
                        */
                        'nome_notificacao' => null,
                        'email_notificacao' => null,
                        'telefone_notificacao' => null,
                        'notificar_email' => false,
                        'notificar_sms' => false,
                        'activo' => true,
                    ]);
                }

                return $resultado;
            });

            return response()->json([
                'status' => 1,
                'message' => 'Conta criada com sucesso.',
                'conta_id' => $resultado['conta']->id,
            ]);

        } catch (\Throwable $e) {

            report($e);

            return response()->json([
                'status' => 0,
                'message' => 'Não foi possível criar a conta.'. $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id)
    {
        /*
        |--------------------------------------------------------------------------
        | CONTA
        |--------------------------------------------------------------------------
        */
        $conta = DB::table('contas')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        abort_if(!$conta, 404);

        /*
        |--------------------------------------------------------------------------
        | CARTEIRA
        |--------------------------------------------------------------------------
        */
        $carteira = DB::table('carteiras')
            ->where('conta_id', $id)
            ->whereNull('deleted_at')
            ->first();

        /*
        |--------------------------------------------------------------------------
        | TARIFA ASSOCIADA
        |--------------------------------------------------------------------------
        */
        $tarifa = null;

        if ($conta->tarifa_sms_id) {
            $tarifa = DB::table('tarifas_sms')
                ->where('id', $conta->tarifa_sms_id)
                ->first();
        }

        /*
        |--------------------------------------------------------------------------
        | TARIFAS DISPONÍVEIS
        |--------------------------------------------------------------------------
        | Gerais ou exclusivas desta conta.
        |--------------------------------------------------------------------------
        */
        $tarifasDisponiveis = DB::table('tarifas_sms')
            ->where('activo', 1)
            ->where(function ($query) use ($id) {
                $query->whereNull('conta_id')
                    ->orWhere('conta_id', $id);
            })
            ->orderBy('quantidade_minima')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | ÚLTIMOS 3 PAGAMENTOS
        |--------------------------------------------------------------------------
        */
        $pagamentos = DB::table('pagamentos')
            ->where('conta_id', $id)
            ->orderByDesc('id')
            ->limit(3)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | ÚLTIMAS 3 FACTURAS
        |--------------------------------------------------------------------------
        */
        $facturas = DB::table('facturas_sms')
            ->where('conta_id', $id)
            ->orderByDesc('id')
            ->limit(3)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | ÚLTIMAS 3 COMPRAS DE SMS
        |--------------------------------------------------------------------------
        */
        $compras = DB::table('compras_sms')
            ->where('conta_id', $id)
            ->orderByDesc('id')
            ->limit(3)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | LOTES DE SMS
        |--------------------------------------------------------------------------
        */
        $lotes = DB::table('carteira_lotes_sms')
            ->where('conta_id', $id)
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | UTILIZADORES DA CONTA
        |--------------------------------------------------------------------------
        */
        $utilizadores = DB::table('conta_user as cu')
            ->join('users as u', 'u.id', '=', 'cu.user_id')
            ->leftJoin('roles as r', 'r.id', '=', 'cu.role_id')
            ->where('cu.conta_id', $id)
            ->whereNull('cu.removido_em')
            ->select(
                'cu.id',
                'cu.estado as estado_associacao',
                'u.id as user_id',
                'u.name',
                'u.email',
                'u.telefone',
                'u.estado as estado_utilizador',
                'u.ultimo_login_em',
                'r.nome as role_nome',
                'r.codigo as role_codigo'
            )
            ->orderBy('u.name')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | SENDER IDS
        |--------------------------------------------------------------------------
        | O relacionamento ainda precisa de confirmação.
        |--------------------------------------------------------------------------
        */
        $senderIds = collect();

        return view('admin.contas.show', compact(
            'conta',
            'carteira',
            'tarifa',
            'tarifasDisponiveis',
            'pagamentos',
            'facturas',
            'compras',
            'lotes',
            'utilizadores',
            'senderIds'
        ));
    }
}