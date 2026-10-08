<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompraStockSms;
use App\Services\CompraStockSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class CompraStockSmsController extends Controller
{
    public function index()
    {
        $compras = CompraStockSms::query()
            ->with('criadoPor')
            ->orderByDesc('data_compra')
            ->orderByDesc('id')
            ->get();

        $stockDisponivel = CompraStockSms::query()
            ->where('estado', 'ACTIVO')
            ->sum('quantidade_disponivel');

        $totalAdquirido = CompraStockSms::query()
            ->where('estado', '!=', 'CANCELADO')
            ->sum('quantidade_sms');

        $valorInvestido = CompraStockSms::query()
            ->where('estado', '!=', 'CANCELADO')
            ->sum('valor_total');

        return view('admin.stock.index', compact('compras','stockDisponivel','totalAdquirido','valorInvestido'));
    }

    public function store(Request $request, CompraStockSmsService $service) {
        
        $dados = $request->validate([
            'quantidade_sms' => [
                'required',
                'integer',
                'min:1',
            ],

            'preco_unitario' => [
                'required',
                'numeric',
                'gt:0',
                'decimal:0,4',
            ],

            'data_compra' => [
                'required',
                'date',
            ],

            'referencia' => [
                'nullable',
                'string',
                'max:100',
            ],

            'observacao' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        try {

            $compra = $service->criar(
                quantidade: (int) $dados['quantidade_sms'],
                precoUnitario: (string) $dados['preco_unitario'],
                dataCompra: $dados['data_compra'],
                referencia: $dados['referencia'] ?? null,
                observacao: $dados['observacao'] ?? null,
                userId: Auth::id(),
                operadora: 'MOVITEL'
            );

            return response()->json([
                'status' => 1,
                'message' => 'Compra de stock registada com sucesso.',
                'compra_id' => $compra->id,
            ]);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status' => 0,
                'message' => 'Não foi possível registar a compra de stock.'
            ], 500);
        }
    }
}