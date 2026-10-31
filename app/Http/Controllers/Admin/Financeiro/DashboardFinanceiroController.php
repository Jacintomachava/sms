<?php

namespace App\Http\Controllers\Admin\Financeiro;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardFinanceiroController extends Controller
{
    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | FILTROS
        |--------------------------------------------------------------------------
        */
        $dados = $request->validate([
            'mes' => 'nullable|integer|between:1,12',
            'ano' => 'nullable|integer|between:2020,2100',
        ]);

        $mes = (int) ($dados['mes'] ?? now()->month);
        $ano = (int) ($dados['ano'] ?? now()->year);

        $inicio = Carbon::create($ano, $mes, 1)->startOfMonth();
        $fim = $inicio->copy()->endOfMonth();

        /*
        |--------------------------------------------------------------------------
        | 1. VENDAS PRÉ-PAGAS
        |--------------------------------------------------------------------------
        | Apenas compras com estado PAGA.
        | A data da venda é a data de criação do registo.
        |--------------------------------------------------------------------------
        */
        $comprasPagas = DB::table('compras_sms')
            ->where('estado', 'PAGA')
            ->whereBetween('created_at', [$inicio, $fim]);

        $vendasPrePago = (float) (clone $comprasPagas)
            ->sum('valor_total');

        $quantidadeVendida = (int) (clone $comprasPagas)
            ->sum('quantidade_sms');

        $numeroCompras = (int) (clone $comprasPagas)
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 2. FACTURAÇÃO PÓS-PAGA
        |--------------------------------------------------------------------------
        | Excluímos facturas canceladas.
        | A facturação é agrupada pela data de emissão.
        |--------------------------------------------------------------------------
        */
        $facturasMes = DB::table('facturas_sms')
            ->where('estado', '!=', 'CANCELADA')
            ->whereBetween('data_emissao', [
                $inicio->toDateString(),
                $fim->toDateString()
            ]);

        $facturacaoPosPago = (float) (clone $facturasMes)
            ->sum('valor_total');

        $numeroFacturas = (int) (clone $facturasMes)
            ->count();

        $facturasPendentes = (int) DB::table('facturas_sms')
            ->where('estado', 'PENDENTE')
            ->count();

        $facturasVencidas = (int) DB::table('facturas_sms')
            ->where(function ($query) {
                $query->where('estado', 'VENCIDA')
                    ->orWhere(function ($subquery) {
                        $subquery->where('estado', 'PENDENTE')
                            ->whereDate('data_vencimento', '<', today());
                    });
            })
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 3. RECEBIMENTOS
        |--------------------------------------------------------------------------
        | A tabela pagamentos representa os pagamentos registados.
        | Não somamos transacoes_pagamento para evitar duplicação.
        |--------------------------------------------------------------------------
        */
        $pagamentosMes = DB::table('pagamentos')
            ->whereBetween(
                DB::raw('COALESCE(pago_em, paga_em)'),
                [$inicio, $fim]
            );

        $recebimentos = (float) (clone $pagamentosMes)
            ->sum('valor');

        $recebimentosPrePago = (float) (clone $pagamentosMes)
            ->where('tipo', 'COMPRA_SMS')
            ->sum('valor');

        $recebimentosPosPago = (float) (clone $pagamentosMes)
            ->where('tipo', 'FACTURA')
            ->sum('valor');

        /*
        |--------------------------------------------------------------------------
        | 4. CONTAS A RECEBER
        |--------------------------------------------------------------------------
        | Calculamos o saldo das facturas não canceladas.
        | Usamos factura_pagamentos para identificar pagamentos aplicados.
        |--------------------------------------------------------------------------
        */
        $pagamentosPorFactura = DB::table('factura_pagamentos')
            ->select('factura_sms_id')
            ->selectRaw('SUM(valor_aplicado) AS total_pago')
            ->groupBy('factura_sms_id');

        $contasReceber = (float) DB::table('facturas_sms as f')
            ->leftJoinSub($pagamentosPorFactura, 'fp', function ($join) {
                $join->on('fp.factura_sms_id', '=', 'f.id');
            })
            ->where('f.estado', '!=', 'CANCELADA')
            ->selectRaw('
                COALESCE(
                    SUM(
                        GREATEST(
                            f.valor_total - COALESCE(fp.total_pago, 0),
                            0
                        )
                    ),
                    0
                ) AS total
            ')
            ->value('total');

        /*
        |--------------------------------------------------------------------------
        | 5. RECEITA DE CONSUMO PRÉ-PAGO
        |--------------------------------------------------------------------------
        | Utilizamos o preço efectivamente associado ao crédito consumido.
        |--------------------------------------------------------------------------
        */
        $receitaConsumoPrePago = (float) DB::table('sms_consumos_creditos')
            ->whereBetween('created_at', [$inicio, $fim])
            ->sum('valor_total');

        $segmentosPrePago = (int) DB::table('sms_consumos_creditos')
            ->whereBetween('created_at', [$inicio, $fim])
            ->sum('segmentos');

        /*
        |--------------------------------------------------------------------------
        | 6. CUSTOS MOVITEL
        |--------------------------------------------------------------------------
        */
        $custoMovitel = (float) DB::table('sms_consumos_stock')
            ->whereBetween('created_at', [$inicio, $fim])
            ->sum('custo_total');

        $segmentosConsumidos = (int) DB::table('sms_consumos_stock')
            ->whereBetween('created_at', [$inicio, $fim])
            ->sum('segmentos');

        /*
        |--------------------------------------------------------------------------
        | 7. MARGEM BRUTA PRÉ-PAGA
        |--------------------------------------------------------------------------
        | Relacionamos os consumos de créditos e stock pelo sms_id.
        | Agregamos primeiro por SMS para evitar multiplicar valores
        | quando existem vários lotes de crédito ou stock.
        |--------------------------------------------------------------------------
        */
        $receitasPorSms = DB::table('sms_consumos_creditos')
            ->select('sms_id')
            ->selectRaw('SUM(valor_total) AS receita')
            ->groupBy('sms_id');

        $custosPorSms = DB::table('sms_consumos_stock')
            ->select('sms_id')
            ->selectRaw('SUM(custo_total) AS custo')
            ->groupBy('sms_id');

        $margemPrePagoDados = DB::table('sms_consumos_creditos as cc')
            ->joinSub($receitasPorSms, 'r', function ($join) {
                $join->on('r.sms_id', '=', 'cc.sms_id');
            })
            ->leftJoinSub($custosPorSms, 'c', function ($join) {
                $join->on('c.sms_id', '=', 'cc.sms_id');
            })
            ->whereBetween('cc.created_at', [$inicio, $fim])
            ->selectRaw('
                cc.sms_id,
                MAX(r.receita) AS receita,
                MAX(COALESCE(c.custo, 0)) AS custo
            ')
            ->groupBy('cc.sms_id')
            ->get();

        $receitaMargemPrePago = (float) $margemPrePagoDados
            ->sum('receita');

        $custoMargemPrePago = (float) $margemPrePagoDados
            ->sum('custo');

        $margemBrutaPrePago = $receitaMargemPrePago
            - $custoMargemPrePago;

        $percentagemMargemPrePago = $receitaMargemPrePago > 0
            ? round(
                ($margemBrutaPrePago / $receitaMargemPrePago) * 100,
                2
            )
            : 0;

        /*
        |--------------------------------------------------------------------------
        | 8. CRÉDITOS AINDA NÃO UTILIZADOS
        |--------------------------------------------------------------------------
        */
        $creditosNaoUtilizados = (int) DB::table('carteira_lotes_sms')
            ->where('estado', 'ACTIVO')
            ->selectRaw('
                COALESCE(
                    SUM(quantidade_disponivel + quantidade_reservada),
                    0
                ) AS total
            ')
            ->value('total');

        $valorCreditosNaoUtilizados = (float) DB::table('carteira_lotes_sms')
            ->where('estado', 'ACTIVO')
            ->selectRaw('
                COALESCE(
                    SUM(
                        (quantidade_disponivel + quantidade_reservada)
                        * preco_venda_unitario
                    ),
                    0
                ) AS total
            ')
            ->value('total');

        /*
        |--------------------------------------------------------------------------
        | 9. INVESTIMENTO EM STOCK
        |--------------------------------------------------------------------------
        */
        $investimentoStock = (float) DB::table('compras_stock_sms')
            ->where('operadora', 'MOVITEL')
            ->sum('valor_total');

        /*
        |--------------------------------------------------------------------------
        | 10. ÚLTIMAS FACTURAS
        |--------------------------------------------------------------------------
        */
        $ultimasFacturas = DB::table('facturas_sms')
            ->select(
                'id',
                'conta_id',
                'numero',
                'data_emissao',
                'data_vencimento',
                'valor_total',
                'estado'
            )
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 11. GRÁFICO: ÚLTIMOS 6 MESES
        |--------------------------------------------------------------------------
        */
        $grafico = [];

        for ($i = 5; $i >= 0; $i--) {

            $data = now()->startOfMonth()->subMonths($i);

            $inicioGrafico = $data->copy()->startOfMonth();
            $fimGrafico = $data->copy()->endOfMonth();

            $vendas = (float) DB::table('compras_sms')
                ->where('estado', 'PAGA')
                ->whereBetween('created_at', [
                    $inicioGrafico,
                    $fimGrafico
                ])
                ->sum('valor_total');

            $facturacao = (float) DB::table('facturas_sms')
                ->where('estado', '!=', 'CANCELADA')
                ->whereBetween('data_emissao', [
                    $inicioGrafico->toDateString(),
                    $fimGrafico->toDateString()
                ])
                ->sum('valor_total');

            $custos = (float) DB::table('sms_consumos_stock')
                ->whereBetween('created_at', [
                    $inicioGrafico,
                    $fimGrafico
                ])
                ->sum('custo_total');

            $grafico[] = [
                'mes' => $data->format('m/Y'),
                'vendas' => $vendas,
                'facturacao' => $facturacao,
                'custos' => $custos,
            ];
        }

        return view('admin.financeiro.dashboard', compact(
            'mes',
            'ano',
            'vendasPrePago',
            'quantidadeVendida',
            'numeroCompras',
            'facturacaoPosPago',
            'numeroFacturas',
            'facturasPendentes',
            'facturasVencidas',
            'recebimentos',
            'recebimentosPrePago',
            'recebimentosPosPago',
            'contasReceber',
            'receitaConsumoPrePago',
            'segmentosPrePago',
            'custoMovitel',
            'segmentosConsumidos',
            'margemBrutaPrePago',
            'percentagemMargemPrePago',
            'creditosNaoUtilizados',
            'valorCreditosNaoUtilizados',
            'investimentoStock',
            'ultimasFacturas',
            'grafico'
        ));
    }
}