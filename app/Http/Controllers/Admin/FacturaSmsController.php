<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conta;
use App\Models\FacturaSms;
use App\Models\Pagamento;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\Pagamentos\PagamentoFacturaSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class FacturaSmsController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LISTAR FACTURAS
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $query = FacturaSms::query()
            ->with('conta')
            ->withSum(
                'pagamentos as total_pago',
                'factura_pagamentos.valor_aplicado'
            );

        if ($request->filled('conta_id')) {
            $query->where('conta_id', $request->conta_id);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $facturas = $query
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $contas = Conta::query()
            ->where('tipo_cobranca', 'POS_PAGO')
            ->orderBy('nome')
            ->get(['id', 'nome']);

        return view(
            'admin.facturas.index',
            compact('facturas', 'contas')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | VISUALIZAR FACTURA
    |--------------------------------------------------------------------------
    */
    public function show(FacturaSms $factura)
    {

        $factura->load([
            'conta',
            'pagamentos' => function ($query) {
                $query->orderByDesc('pagamentos.id');
            }
        ]);

        return view('admin.facturas.show', compact('factura'));
    }

    /*
    |--------------------------------------------------------------------------
    | REGISTAR PAGAMENTO
    |--------------------------------------------------------------------------
    */
    public function registarPagamento(
        Request $request,
        PagamentoFacturaSmsService $service
    ) {


        $dados = $request->validate([
            'conta_id' => [
                'required',
                'integer',
                'exists:contas,id'
            ],

            'forma_pagamento' => [
                'required',
                Rule::in([
                    'CASH',
                    'DEPOSITO',
                    'TRANSFERENCIA',
                ])
            ],

            'referencia' => [
                'nullable',
                'string',
                'max:150',
            ],

            'chave_idempotencia' => [
                'required',
                'uuid',
            ],

            'facturas' => [
                'required',
                'array',
                'min:1',
            ],

            'facturas.*.factura_id' => [
                'required',
                'integer',
                'distinct',
                'exists:facturas_sms,id',
            ],

            'facturas.*.valor' => [
                'required',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
        ]);

        try {

            $pagamento = $service->registar(
                contaId: (int) $dados['conta_id'],
                formaPagamento: $dados['forma_pagamento'],
                facturas: $dados['facturas'],
                referencia: $dados['referencia'] ?? null,
                userId: auth()->id(),
                chaveIdempotencia: $dados['chave_idempotencia']
            );

            return response()->json([
                'status' => 1,
                'message' => 'Pagamento registado com sucesso.',
                'pagamento_id' => $pagamento->id,
                'valor' => $pagamento->valor,
            ]);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status' => 0,
                'message' => 'Não foi possível registar o pagamento.'. $e->getMessage(),
            ], 422);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | HISTÓRICO DE PAGAMENTOS
    |--------------------------------------------------------------------------
    */
   public function pagamentos(Request $request)
    {
        $query = Pagamento::query()
            ->with(['conta', 'facturas'])
            ->where('tipo', 'FACTURA');

        if ($request->filled('conta_id')) {
            $query->where('conta_id', $request->conta_id);
        }

        if ($request->filled('forma_pagamento')) {
            $query->where(
                'forma_pagamento',
                $request->forma_pagamento
            );
        }

        $pagamentos = $query
            ->orderByDesc('pago_em')
            ->paginate(20)
            ->withQueryString();

        $contas = Conta::query()
            ->where('tipo_cobranca', 'POS_PAGO')
            ->orderBy('nome')
            ->get(['id', 'nome']);

        return view(
            'admin.facturas.pagamentos',
            compact('pagamentos', 'contas')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | GERAR PDF DA FACTURA
    |--------------------------------------------------------------------------
    */
    public function pdf(FacturaSms $factura)
    {
        $factura->load([
            'conta',
            'pagamentos',
        ]);

        $totalPago = '0.00';

        foreach ($factura->pagamentos as $pagamento) {
            $totalPago = bcadd(
                $totalPago,
                (string) $pagamento->pivot->valor_aplicado,
                2
            );
        }

        $saldo = bcsub(
            (string) $factura->valor_total,
            $totalPago,
            2
        );

        if (bccomp($saldo, '0.00', 2) < 0) {
            $saldo = '0.00';
        }

        $pdf = Pdf::loadView('admin.facturas.pdf', [
            'factura' => $factura,
            'totalPago' => $totalPago,
            'saldo' => $saldo,
        ])->setPaper('a4', 'portrait');

        $nomeFicheiro = 'Factura-' .
            Str::slug($factura->numero) .
            '.pdf';

        return $pdf->download($nomeFicheiro);
    }

    /*
    |--------------------------------------------------------------------------
    | NOVO PAGAMENTO DE VÁRIAS FACTURAS
    |--------------------------------------------------------------------------
    */
    public function novoPagamento(Request $request)
    {
        $contas = Conta::query()
            ->where('tipo_cobranca', 'POS_PAGO')
            ->where('estado', 'ACTIVA')
            ->orderBy('nome')
            ->get(['id', 'nome']);

        $contaId = $request->integer('conta_id');

        $facturas = collect();

        if ($contaId > 0) {

            $facturas = FacturaSms::query()
                ->where('conta_id', $contaId)
                ->whereIn('estado', ['PENDENTE', 'VENCIDA'])
                ->withSum(
                    'pagamentos as total_pago',
                    'factura_pagamentos.valor_aplicado'
                )
                ->orderBy('data_emissao')
                ->get()
                ->filter(function ($factura) {

                    $saldo = bcsub(
                        (string) $factura->valor_total,
                        (string) ($factura->total_pago ?? '0.00'),
                        2
                    );

                    return bccomp($saldo, '0.00', 2) > 0;
                })
                ->values();
        }

        return view(
            'admin.facturas.novo-pagamento',
            compact('contas', 'contaId', 'facturas')
        );
    }

}