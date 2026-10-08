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
                'message' => 'Não foi possível criar a conta.',
            ], 500);
        }
    }
}