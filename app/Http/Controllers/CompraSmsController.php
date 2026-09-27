<?php

namespace App\Http\Controllers;

use App\Models\CompraSms;
use App\Services\CompraSmsService;
use App\Services\TarifaSmsService;
use App\Services\Pagamentos\PagamentoSmsService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class CompraSmsController extends Controller
{
    public function index()
    {
        $contaId = (int) session('conta_id');

        $compras = CompraSms::query()
            ->where('conta_id', $contaId)
            ->with([
                'tarifa',
                'pagamento'
            ])
            ->latest()
            ->get();

        $carteira = \App\Models\Carteira::where(
            'conta_id',
            $contaId
        )->first();

        $saldoSms = $carteira?->saldo_sms ?? 0;

        $totalComprado = CompraSms::where('conta_id', $contaId)
            ->where('estado', 'PAGA')
            ->sum('quantidade_sms');

        $totalInvestido = CompraSms::where('conta_id', $contaId)
            ->where('estado', 'PAGA')
            ->sum('valor_total');

        $comprasPendentes = CompraSms::where('conta_id', $contaId)
            ->where('estado', 'PENDENTE')
            ->count();

        return view('compras-sms.index', compact(
            'compras',
            'carteira',
            'saldoSms',
            'totalComprado',
            'totalInvestido',
            'comprasPendentes'
        ));
    }


    /**
     * Calcula o preço antes de criar a compra.
     */
    public function calcular(Request $request, TarifaSmsService $tarifaService) {

        $request->validate([
            'quantidade_sms' => [
                'required',
                'integer',
                'min:1'
            ],
        ]);

        try {

            $contaId = (int) session('conta_id');

            $calculo = $tarifaService->calcular(
                $contaId,
                (int) $request->quantidade_sms
            );

            return response()->json([
                'status' => 1,
                'data' => [
                    'quantidade_sms' => $calculo['quantidade'],
                    'preco_unitario' => number_format($calculo['preco_unitario'],2,'.',''),
                    'valor_total' => number_format($calculo['valor_total'], 2,'.',''),
                    'tarifa' => $calculo['tarifa']->nome,
                ],
            ]);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status' => 0,
                'message' => 'Não foi possível calcular a tarifa.',
            ], 422);
        }
    }


    /**
     * Cria compra e inicia pagamento M-Pesa.
     */
    public function comprar(Request $request, CompraSmsService $compraService, PagamentoSmsService $pagamentoService) {

        $request->validate([
            'quantidade_sms' => ['required','integer','min:1'],
            'telefone' => ['required','regex:/^[0-9]{9}$/'],
        ]);

        $contaId = (int) session('conta_id');
        $userId = auth()->id();

        try {

            /*
             * IMPORTANTE:
             *
             * O preço não vem do frontend.
             * Recalculamos tudo no servidor.
             */
            $compra = $compraService->criar(
                $contaId,
                (int) $request->quantidade_sms,
                $userId
            );

            /*
             * Agora iniciamos a tentativa M-Pesa.
             */
            $resultado = $pagamentoService->pagarMpesa(
                $compra->id,
                $contaId,
                $request->telefone,
                $userId
            );

            if (!$resultado['sucesso']) {

                return response()->json([
                    'status' => 0,
                    'message' => $resultado['message'],
                    'compra_id' => $compra->id,
                    'transacao_id' => $resultado['transacao']->id ?? null,
                    /*
                     * A compra continua PENDENTE.
                     * Poderemos permitir nova tentativa.
                     */
                    'pode_tentar_novamente' => true,
                ], 422);
            }

            return response()->json([
                'status' => 1,
                'message' => 'Pagamento efectuado com sucesso.',
                'compra_id' => $compra->id,
                'pagamento_id' => $resultado['pagamento']->id,
                'saldo_sms' =>
                    auth()->user()
                        ?->contas()
                        ->where('contas.id', $contaId)
                        ->first()
                        ?->carteira
                        ?->saldo_sms,
            ]);

        } catch (ValidationException $e) {
            throw $e;

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status' => 0,
                'message' => 'Ocorreu um erro ao processar a compra.',
            ], 500);
        }
    }

    public function pagar(Request $request, int $id, PagamentoSmsService $pagamentoService) {
        
        $request->validate([
            'telefone' => ['required','regex:/^(84|85)[0-9]{7}$/'],
        ]);

        $contaId = (int) session('conta_id');
        $userId = auth()->id();

        /*
        * A compra tem obrigatoriamente de pertencer
        * à conta actualmente autenticada.
        */
        $compra = CompraSms::query()
            ->where('id', $id)
            ->where('conta_id', $contaId)
            ->firstOrFail();

        /*
        * Só compras pendentes podem ser pagas novamente.
        */
        if ($compra->estado !== 'PENDENTE') {

            return response()->json([
                'status' => 0,
                'message' => 'Esta compra já não está pendente.',
            ], 422);
        }

        try {

            /*
            * Não criamos uma nova compra.
            *
            * Criamos apenas uma nova tentativa de pagamento
            * para a compra existente.
            */
            $resultado = $pagamentoService->pagarMpesa(
                $compra->id,
                $contaId,
                $request->telefone,
                $userId
            );

            if (!$resultado['sucesso']) {

                return response()->json([
                    'status' => 0,

                    'message' =>
                        $resultado['message']
                        ?? 'Não foi possível efectuar o pagamento.',

                    'compra_id' =>
                        $compra->id,

                    'transacao_id' =>
                        $resultado['transacao']->id ?? null,

                ], 422);
            }

            return response()->json([
                'status' => 1,

                'message' =>
                    'Pagamento efectuado e créditos SMS adicionados com sucesso.',

                'compra_id' =>
                    $compra->id,

                'transacao_id' =>
                    $resultado['transacao']->id,

                'pagamento_id' =>
                    $resultado['pagamento']->id,

                'saldo_sms' =>
                    \App\Models\Carteira::where(
                        'conta_id',
                        $contaId
                    )->value('saldo_sms'),

            ]);

        } catch (\Throwable $e) {

            report($e);

            return response()->json([
                'status' => 0,
                'message' =>
                    'Não foi possível processar o pagamento.',
            ], 500);
        }
    }
}