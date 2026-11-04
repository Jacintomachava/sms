<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PagamentoMonitorController extends Controller
{
    private function consultaPagamentos()
    {
        // Agregação para evitar duplicar pagamentos que
        // liquidaram várias facturas.
        $facturas = DB::table('factura_pagamentos as fp')
            ->join('facturas_sms as f', 'f.id', '=', 'fp.factura_sms_id')
            ->select(
                'fp.pagamento_id',
                DB::raw('COUNT(DISTINCT f.id) as total_facturas'),
                DB::raw('GROUP_CONCAT(DISTINCT f.numero ORDER BY f.numero SEPARATOR ", ") as numeros_facturas')
            )
            ->groupBy('fp.pagamento_id');

        return DB::table('pagamentos as p')
            ->leftJoin('contas as c', 'c.id', '=', 'p.conta_id')
            ->leftJoin(
                'compras_sms as cs',
                'cs.id',
                '=',
                'p.compra_sms_id'
            )
            ->leftJoin(
                'transacoes_pagamento as t',
                't.id',
                '=',
                'p.transacao_pagamento_id'
            )
            ->leftJoinSub($facturas, 'faturas', function ($join) {
                $join->on('faturas.pagamento_id', '=', 'p.id');
            })
            ->select(
                'p.*',
                'c.nome as conta_nome',
                'c.nome_legal as conta_nome_legal',
                'c.tipo_cobranca',
                'cs.quantidade_sms',
                'cs.estado as compra_estado',
                't.estado as transacao_estado',
                't.provider',
                't.provider_codigo',
                't.provider_descricao',
                't.provider_transacao_id',
                't.confirmada_em',
                'faturas.total_facturas',
                'faturas.numeros_facturas'
            );
    }

    public function index(Request $request)
    {
        $filtros = $request->validate([
            'conta_id' => ['nullable', 'integer', 'min:1'],
            'tipo' => ['nullable', Rule::in(['SMS', 'FACTURA'])],
            'estado' => ['nullable', 'string', 'max:50'],
            'inicio' => ['nullable', 'date'],
            'fim' => ['nullable', 'date', 'after_or_equal:inicio'],
        ]);

        $query = $this->consultaPagamentos();

        // Filtrar por conta
        if (!empty($filtros['conta_id'])) {
            $query->where('p.conta_id', $filtros['conta_id']);
        }

        // Tipo de pagamento
        if (($filtros['tipo'] ?? '') === 'SMS') {
            $query->whereNotNull('p.compra_sms_id');
        }

        if (($filtros['tipo'] ?? '') === 'FACTURA') {
            $query->whereExists(function ($subquery) {
                $subquery->selectRaw('1')
                    ->from('factura_pagamentos as fp')
                    ->whereColumn('fp.pagamento_id', 'p.id');
            });
        }

        // Estado da transacção associada
        if (!empty($filtros['estado'])) {
            $query->where('t.estado', $filtros['estado']);
        }

        // Período de registo do pagamento
        if (!empty($filtros['inicio'])) {
            $query->whereDate(
                'p.created_at',
                '>=',
                $filtros['inicio']
            );
        }

        if (!empty($filtros['fim'])) {
            $query->whereDate(
                'p.created_at',
                '<=',
                $filtros['fim']
            );
        }

        $pagamentos = $query
            ->orderByDesc('p.id')
            ->paginate(25)
            ->withQueryString();

        $contas = DB::table('contas')
            ->select('id', 'nome', 'nome_legal')
            ->orderBy('nome')
            ->get();

        $estados = DB::table('transacoes_pagamento')
            ->whereNotNull('estado')
            ->distinct()
            ->orderBy('estado')
            ->pluck('estado');

        return view('admin.pagamentos.index', compact(
            'pagamentos',
            'contas',
            'estados'
        ));
    }

    public function show(int $id)
    {
        $pagamento = $this->consultaPagamentos()
            ->where('p.id', $id)
            ->first();

        abort_if(!$pagamento, 404);

        $transacao = null;

        if ($pagamento->transacao_pagamento_id) {
            $transacao = DB::table('transacoes_pagamento')
                ->where('id', $pagamento->transacao_pagamento_id)
                ->first();
        }

        $compra = null;

        if ($pagamento->compra_sms_id) {
            $compra = DB::table('compras_sms')
                ->where('id', $pagamento->compra_sms_id)
                ->first();
        }

        $facturas = DB::table('factura_pagamentos as fp')
            ->join(
                'facturas_sms as f',
                'f.id',
                '=',
                'fp.factura_sms_id'
            )
            ->where('fp.pagamento_id', $id)
            ->select(
                'fp.id as associacao_id',
                'fp.valor_aplicado',
                'f.id',
                'f.numero',
                'f.valor_total',
                'f.moeda',
                'f.estado',
                'f.data_emissao',
                'f.data_vencimento',
                'f.paga_em'
            )
            ->orderBy('f.id')
            ->get();

        $totalAplicado = (float) $facturas->sum('valor_aplicado');

        return view('admin.pagamentos.show', compact(
            'pagamento',
            'transacao',
            'compra',
            'facturas',
            'totalAplicado'
        ));
    }
}
