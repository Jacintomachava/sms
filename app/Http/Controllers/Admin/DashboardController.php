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
        | STOCK FÍSICO MOVITEL
        |--------------------------------------------------------------------------
        */
        $stock = DB::table('compras_stock_sms')
            ->where('estado', 'ACTIVO')
            ->where('operadora', 'MOVITEL')
            ->selectRaw('
                COALESCE(SUM(quantidade_sms), 0) as comprado,
                COALESCE(SUM(quantidade_disponivel), 0) as disponivel_lote,
                COALESCE(SUM(quantidade_reservada), 0) as reservado
            ')
            ->first();

        $stockComprado = (int) $stock->comprado;

        $stockFisico = (int) $stock->disponivel_lote
            + (int) $stock->reservado;

        $stockReservado = (int) $stock->reservado;

        /*
        |--------------------------------------------------------------------------
        | CRÉDITOS PRÉ-PAGOS
        |--------------------------------------------------------------------------
        */
        $creditosPrePago = (int) DB::table('carteira_lotes_sms')
            ->where('estado', 'ACTIVO')
            ->sum('quantidade_disponivel');

        /*
        |--------------------------------------------------------------------------
        | RESERVAS POR MODALIDADE
        |--------------------------------------------------------------------------
        */
        $reservasPrePago = (int) DB::table('sms_reservas')
            ->where('tipo', 'CLIENTE')
            ->where('estado', 'RESERVADA')
            ->sum('quantidade');

        $reservasPosPago = (int) DB::table('sms_reservas')
            ->where('tipo', 'POS_PAGO')
            ->where('estado', 'RESERVADA')
            ->sum('quantidade');

        /*
        |--------------------------------------------------------------------------
        | STOCK COMPROMETIDO
        |--------------------------------------------------------------------------
        | O stock físico inclui o stock reservado.
        | Os créditos pré-pagos aqui considerados são apenas os livres.
        */
        $stockComprometido = $creditosPrePago + $stockReservado;

        $stockLivre = $stockFisico - $stockComprometido;

        /*
        |--------------------------------------------------------------------------
        | COBERTURA
        |--------------------------------------------------------------------------
        */
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
        | ESTATÍSTICAS SMS
        |--------------------------------------------------------------------------
        */
        $smsEstados = DB::table('sms')
            ->select('estado')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $smsEnviadas = (int) ($smsEstados['ENVIADO'] ?? 0);
        $smsEntregues = (int) ($smsEstados['ENTREGUE'] ?? 0);
        $smsFalhadas = (int) ($smsEstados['FALHADO'] ?? 0);

        $smsSemDlr = DB::table('sms')
            ->where('estado', 'ENVIADO')
            ->whereNull('dlr_estado')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | TENTATIVAS SMPP INCERTAS
        |--------------------------------------------------------------------------
        */
        $tentativasIncertas = DB::table('smpp_tentativas_envio')
            ->where('estado', 'INCERTA')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | CONSUMO DE STOCK
        |--------------------------------------------------------------------------
        */
        $consumoHoje = (int) DB::table('sms_consumos_stock')
            ->whereDate('created_at', today())
            ->sum('segmentos');

        $consumoMes = (int) DB::table('sms_consumos_stock')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('segmentos');

        /*
        |--------------------------------------------------------------------------
        | AUTONOMIA
        |--------------------------------------------------------------------------
        */
        $inicioMes = now()->startOfMonth();

        $diasDecorridos = $inicioMes->diffInDays(now()) + 1;

        $mediaDiaria = $diasDecorridos > 0
            ? $consumoMes / $diasDecorridos
            : 0;

        $autonomia = $mediaDiaria > 0
            ? round(max(0, $stockLivre) / $mediaDiaria, 1)
            : null;

        /*
        |--------------------------------------------------------------------------
        | RECONCILIAÇÃO DE STOCK
        |--------------------------------------------------------------------------
        */

        $stockConsumido = (int) DB::table('sms_consumos_stock')->sum('segmentos');

        $stockTotal = DB::table('compras_stock_sms')
            ->where('operadora', 'MOVITEL')
            ->selectRaw('
                COALESCE(SUM(quantidade_sms), 0) as comprado,
                COALESCE(SUM(quantidade_disponivel), 0) as disponivel,
                COALESCE(SUM(quantidade_reservada), 0) as reservado
            ')
            ->first();

        $stockEsperado = (int) $stockTotal->comprado - $stockConsumido;

        $stockActual = (int) $stockTotal->disponivel + (int) $stockTotal->reservado;
 
        $diferencaStock = $stockActual - $stockEsperado;

        $estadoReconciliacao = $diferencaStock === 0 ? 'CONCILIADO' : 'DIVERGENTE';

        /*
        |--------------------------------------------------------------------------
        | SMS INCERTAS COM STOCK LIBERTADO
        |--------------------------------------------------------------------------
        */

        $smsIncertasLibertadas = DB::table('sms as s')
            ->join('smpp_tentativas_envio as t', 't.sms_id', '=', 's.id')
            ->join('sms_reservas as r', 'r.sms_id', '=', 's.id')
            ->where('t.estado', 'INCERTA')
            ->where('s.estado', 'FALHADO')
            ->where('r.tipo', 'STOCK')
            ->where('r.estado', 'LIBERTADA')
            ->distinct()
            ->count('s.id');

        return view('admin.dashboard.index', compact(
            'stockComprado',
            'stockFisico',
            'stockReservado',
            'creditosPrePago',
            'reservasPrePago',
            'reservasPosPago',
            'stockComprometido',
            'stockLivre',
            'cobertura',
            'estadoCobertura',
            'smsEnviadas',
            'smsEntregues',
            'smsFalhadas',
            'smsSemDlr',
            'tentativasIncertas',
            'consumoHoje',
            'consumoMes',
            'mediaDiaria',
            'autonomia'
        ));
    }
}