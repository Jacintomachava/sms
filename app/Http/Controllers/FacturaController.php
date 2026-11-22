<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Conta;
use App\Models\FacturaSms;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FacturaController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | CONTA AUTORIZADA
    |--------------------------------------------------------------------------
    */
    private function contaActual(): Conta
    {
        $contaId = (int) session('conta_id');

        if ($contaId <= 0) {
            abort(403, 'Nenhuma conta seleccionada.');
        }

        $userId = auth()->id();

        $autorizado = DB::table('conta_user')
            ->where('conta_id', $contaId)
            ->where('user_id', $userId)
            ->exists();

        if (!$autorizado) {
            abort(403, 'Sem acesso a esta conta.');
        }

        $conta = Conta::findOrFail($contaId);

        if ($conta->tipo_cobranca !== 'POS_PAGO') {
            abort(403, 'Disponível apenas para contas pós-pagas.');
        }

        return $conta;
    }

    /*
    |--------------------------------------------------------------------------
    | MINHAS FACTURAS
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $conta = $this->contaActual();

        $query = FacturaSms::query()
            ->where('conta_id', $conta->id)
            ->withSum(
                'pagamentos as total_pago',
                'factura_pagamentos.valor_aplicado'
            );

        if ($request->filled('estado')) {
            $query->whereIn(
                'estado',
                ['PENDENTE', 'PAGA', 'VENCIDA']
            )->where('estado', $request->estado);
        }

        $facturas = $query
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $totais = FacturaSms::query()
            ->where('conta_id', $conta->id)
            ->withSum(
                'pagamentos as total_pago',
                'factura_pagamentos.valor_aplicado'
            )
            ->get();

        $totalFacturado = '0.00';
        $totalPago = '0.00';

        foreach ($totais as $factura) {
            $totalFacturado = bcadd(
                $totalFacturado,
                (string) $factura->valor_total,
                2
            );

            $totalPago = bcadd(
                $totalPago,
                (string) ($factura->total_pago ?? '0.00'),
                2
            );
        }

        $saldoPendente = bcsub(
            $totalFacturado,
            $totalPago,
            2
        );

        if (bccomp($saldoPendente, '0.00', 2) < 0) {
            $saldoPendente = '0.00';
        }

        return view('cliente.facturas.index', compact(
            'conta',
            'facturas',
            'totalFacturado',
            'totalPago',
            'saldoPendente'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | DETALHES DA FACTURA
    |--------------------------------------------------------------------------
    */
    public function show(int $id)
    {
        $conta = $this->contaActual();

        $factura = FacturaSms::query()
            ->where('conta_id', $conta->id)
            ->with([
                'conta',
                'pagamentos' => function ($query) {
                    $query->orderByDesc('pagamentos.id');
                }
            ])
            ->findOrFail($id);

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

        return view('cliente.facturas.show', compact(
            'conta',
            'factura',
            'totalPago',
            'saldo'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | DESCARREGAR PDF
    |--------------------------------------------------------------------------
    */
    public function pdf(int $id)
    {
        $conta = $this->contaActual();

        $factura = FacturaSms::query()
            ->where('conta_id', $conta->id)
            ->with(['conta', 'pagamentos'])
            ->findOrFail($id);

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

        // Reutiliza exactamente o PDF administrativo.
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

}