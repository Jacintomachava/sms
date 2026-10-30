<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        /*
        |--------------------------------------------------------------------------
        | 1. STOCK FÍSICO MOVITEL
        |--------------------------------------------------------------------------
        */
        $stock = DB::table('compras_stock_sms')
            ->where('operadora', 'MOVITEL')
            ->where('estado', 'ACTIVO')
            ->selectRaw('
                COALESCE(SUM(quantidade_sms), 0) AS comprado,
                COALESCE(SUM(quantidade_disponivel), 0) AS disponivel,
                COALESCE(SUM(quantidade_reservada), 0) AS reservado
            ')
            ->first();

        $stockComprado = (int) $stock->comprado;

        $stockDisponivelLote = (int) $stock->disponivel;

        $stockReservado = (int) $stock->reservado;

        // Stock ainda existente, incluindo reservas.
        $stockFisico = $stockDisponivelLote + $stockReservado;

        /*
        |--------------------------------------------------------------------------
        | 2. CRÉDITOS PRÉ-PAGOS
        |--------------------------------------------------------------------------
        */
        $creditosPrePago = (int) DB::table('carteira_lotes_sms')
            ->where('estado', 'ACTIVO')
            ->sum('quantidade_disponivel');

        $creditosPrePagoReservados = (int) DB::table('carteira_lotes_sms')
            ->where('estado', 'ACTIVO')
            ->sum('quantidade_reservada');

        /*
        |--------------------------------------------------------------------------
        | 3. RESERVAS ACTIVAS
        |--------------------------------------------------------------------------
        */
        $reservas = DB::table('sms_reservas')
            ->where('estado', 'RESERVADA')
            ->select('tipo')
            ->selectRaw('COALESCE(SUM(quantidade), 0) AS total')
            ->groupBy('tipo')
            ->pluck('total', 'tipo');

        $reservasPrePago = (int) ($reservas['CLIENTE'] ?? 0);

        $reservasPosPago = (int) ($reservas['POS_PAGO'] ?? 0);

        $reservasStock = (int) ($reservas['STOCK'] ?? 0);

        /*
        |--------------------------------------------------------------------------
        | 4. STOCK COMPROMETIDO E COBERTURA
        |--------------------------------------------------------------------------
        |
        | Os créditos pré-pagos disponíveis já foram vendidos.
        |
        | As reservas de STOCK incluem envios PRE_PAGO e POS_PAGO.
        | Não somamos novamente as reservas CLIENTE/POS_PAGO.
        |
        */
        $stockComprometido = $creditosPrePago + $stockReservado;

        $stockLivre = $stockFisico - $stockComprometido;

        $cobertura = $stockFisico > 0
            ? round(($stockLivre / $stockFisico) * 100, 2)
            : 0;

        if ($stockFisico <= 0) {
            $estadoCobertura = 'SEM_STOCK';
        } elseif ($stockLivre < 0) {
            $estadoCobertura = 'DEFICE';
        } elseif ($cobertura < 40) {
            $estadoCobertura = 'CRITICA';
        } elseif ($cobertura < 70) {
            $estadoCobertura = 'ATENCAO';
        } else {
            $estadoCobertura = 'SAUDAVEL';
        }

        /*
        |--------------------------------------------------------------------------
        | 5. ESTATÍSTICAS SMS
        |--------------------------------------------------------------------------
        */
        $smsEstados = DB::table('sms')
            ->select('estado')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $smsPendentes = (int) ($smsEstados['PENDENTE'] ?? 0);

        $smsProcessando = (int) ($smsEstados['PROCESSANDO'] ?? 0);

        $smsEnviadas = (int) ($smsEstados['ENVIADO'] ?? 0);

        $smsEntregues = (int) ($smsEstados['ENTREGUE'] ?? 0);

        $smsFalhadas = (int) ($smsEstados['FALHADO'] ?? 0);

        $totalSms = (int) DB::table('sms')->count();

        /*
        |--------------------------------------------------------------------------
        | 6. SMS SEM DLR
        |--------------------------------------------------------------------------
        */
        $smsSemDlr = (int) DB::table('sms')
            ->where('estado', 'ENVIADO')
            ->whereNull('dlr_estado')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 7. TENTATIVAS SMPP INCERTAS
        |--------------------------------------------------------------------------
        */
        $tentativasIncertas = (int) DB::table('smpp_tentativas_envio')
            ->where('estado', 'INCERTA')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 8. SMS INCERTAS COM STOCK LIBERTADO
        |--------------------------------------------------------------------------
        */
        $smsIncertasLibertadas = (int) DB::table('sms as s')
            ->join(
                'smpp_tentativas_envio as t',
                't.sms_id',
                '=',
                's.id'
            )
            ->join(
                'sms_reservas as r',
                'r.sms_id',
                '=',
                's.id'
            )
            ->where('t.estado', 'INCERTA')
            ->where('s.estado', 'FALHADO')
            ->where('r.tipo', 'STOCK')
            ->where('r.estado', 'LIBERTADA')
            ->distinct()
            ->count('s.id');

        /*
        |--------------------------------------------------------------------------
        | 9. CONSUMO DE STOCK
        |--------------------------------------------------------------------------
        */
        $consumoHoje = (int) DB::table('sms_consumos_stock')
            ->whereDate('created_at', today())
            ->sum('segmentos');

        $consumoMes = (int) DB::table('sms_consumos_stock')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('segmentos');

        $stockConsumido = (int) DB::table('sms_consumos_stock')
            ->sum('segmentos');

        /*
        |--------------------------------------------------------------------------
        | 10. CUSTO DOS CONSUMOS
        |--------------------------------------------------------------------------
        */
        $custoConsumoHoje = (float) DB::table('sms_consumos_stock')
            ->whereDate('created_at', today())
            ->sum('custo_total');

        $custoConsumoMes = (float) DB::table('sms_consumos_stock')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('custo_total');

        $custoConsumoTotal = (float) DB::table('sms_consumos_stock')
            ->sum('custo_total');

        /*
        |--------------------------------------------------------------------------
        | 11. MÉDIA DIÁRIA E AUTONOMIA
        |--------------------------------------------------------------------------
        */
        $diasDecorridos = now()->day;

        $mediaDiaria = $diasDecorridos > 0
            ? round($consumoMes / $diasDecorridos, 2)
            : 0;

        $autonomia = $mediaDiaria > 0
            ? round(max(0, $stockLivre) / $mediaDiaria, 1)
            : null;

        /*
        |--------------------------------------------------------------------------
        | 12. RECONCILIAÇÃO DE STOCK
        |--------------------------------------------------------------------------
        */
        $stockTotal = DB::table('compras_stock_sms')
            ->where('operadora', 'MOVITEL')
            ->selectRaw('
                COALESCE(SUM(quantidade_sms), 0) AS comprado,
                COALESCE(SUM(quantidade_disponivel), 0) AS disponivel,
                COALESCE(SUM(quantidade_reservada), 0) AS reservado
            ')
            ->first();

        $stockEsperado = (int) $stockTotal->comprado
            - $stockConsumido;

        $stockActual = (int) $stockTotal->disponivel
            + (int) $stockTotal->reservado;

        $diferencaStock = $stockActual - $stockEsperado;

        $estadoReconciliacao = $diferencaStock === 0
            ? 'CONCILIADO'
            : 'DIVERGENTE';

        /*
        |--------------------------------------------------------------------------
        | 13. MOVIMENTOS DE STOCK
        |--------------------------------------------------------------------------
        */
        $movimentosConsumo = (int) DB::table('stock_sms_movimentos')
            ->where('tipo', 'CONSUMO')
            ->sum('quantidade_sms');

        $diferencaMovimentos = $stockConsumido - $movimentosConsumo;

        /*
        |--------------------------------------------------------------------------
        | 14. HISTÓRICO DOS ÚLTIMOS 7 DIAS
        |--------------------------------------------------------------------------
        */
        $consumoDiario = DB::table('sms_consumos_stock')
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->selectRaw('DATE(created_at) AS dia')
            ->selectRaw('SUM(segmentos) AS total')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('dia')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 15. DADOS PARA A VIEW
        |--------------------------------------------------------------------------
        */
        return view('admin.dashboard.index', compact(
            'stockComprado',
            'stockDisponivelLote',
            'stockReservado',
            'stockFisico',
            'creditosPrePago',
            'creditosPrePagoReservados',
            'reservasPrePago',
            'reservasPosPago',
            'reservasStock',
            'stockComprometido',
            'stockLivre',
            'cobertura',
            'estadoCobertura',
            'smsPendentes',
            'smsProcessando',
            'smsEnviadas',
            'smsEntregues',
            'smsFalhadas',
            'totalSms',
            'smsSemDlr',
            'tentativasIncertas',
            'smsIncertasLibertadas',
            'consumoHoje',
            'consumoMes',
            'stockConsumido',
            'custoConsumoHoje',
            'custoConsumoMes',
            'custoConsumoTotal',
            'mediaDiaria',
            'autonomia',
            'stockEsperado',
            'stockActual',
            'diferencaStock',
            'estadoReconciliacao',
            'movimentosConsumo',
            'diferencaMovimentos',
            'consumoDiario'
        ));
    }
}