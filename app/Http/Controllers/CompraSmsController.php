<?php

namespace App\Http\Controllers;

use App\Models\CompraSms;
use App\Services\CompraSmsService;
use App\Services\TarifaSmsService;
use App\Services\Pagamentos\PagamentoSmsService;
use App\Support\RespostaPagamentoSms;
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
    public function comprar(
        Request $request,
        CompraSmsService $compraService,
        PagamentoSmsService $pagamentoService
    ) {
        $dados = $request->validate([
            'quantidade_sms' => ['required', 'integer', 'min:1'],
            'telefone' => [
                'required',
                'string',
                'regex:/^(84|85)[0-9]{7}$/',
            ],
        ]);

        $chave = $request->header('Idempotency-Key');

        if (
            !is_string($chave) ||
            trim($chave) === '' ||
            strlen($chave) > 100
        ) {
            throw ValidationException::withMessages([
                'idempotency_key' =>
                    'A chave de idempotência é obrigatória.',
            ]);
        }

        $contaId = (int) session('conta_id');
        $userId = auth()->id();

        abort_if($contaId <= 0, 403);

        try {

            /*
            * Criar ou recuperar a compra pela chave.
            *
            * Uma repetição do mesmo pedido nunca
            * deve iniciar automaticamente outro débito.
            */
            $criacao = $compraService->criarViaApi(
                $contaId,
                (int) $dados['quantidade_sms'],
                $dados['telefone'],
                $chave
            );

            $compra = $criacao['compra'];

            if (!$criacao['criada']) {

                return response()->json([
                    'status' => 1,
                    'sucesso' => $compra->estado === 'PAGA',
                    'compra_id' => $compra->id,
                    'estado_compra' => $compra->estado,
                    'pagamento_reiniciado' => false,
                    'message' =>
                        'Esta compra já foi registada. Consulte o estado antes de efectuar outro pagamento.',
                ], 200);
            }

            /*
            * Só uma compra recém-criada pode
            * iniciar o primeiro débito M-Pesa.
            */
            $resultado = $pagamentoService->pagarMpesa(
                $compra->id,
                $contaId,
                $dados['telefone'],
                $userId
            );

            $resposta = RespostaPagamentoSms::formatar($resultado);

            $resposta['compra_id'] = $compra->id;

            if ($resposta['sucesso']) {
                $resposta['saldo_sms'] = \App\Models\Carteira::query()
                    ->where('conta_id', $contaId)
                    ->value('saldo_sms');
            }

            return response()->json(
                $resposta,
                RespostaPagamentoSms::httpStatus($resposta)
            );

        } catch (ValidationException $e) {

            throw $e;

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status' => 0,
                'sucesso' => false,
                'message' =>
                    'Não foi possível confirmar o resultado da compra. Consulte o histórico antes de repetir.',
                'pode_tentar_novamente' => false,
            ], 500);
        }
    }

    public function pagar(
        Request $request,
        int $id,
        PagamentoSmsService $pagamentoService
    ) {
        $dados = $request->validate([
            'telefone' => [
                'required',
                'string',
                'regex:/^(84|85)[0-9]{7}$/',
            ],
        ]);

        $contaId = (int) session('conta_id');
        $userId = auth()->id();

        abort_if($contaId <= 0, 403);

        $compra = CompraSms::query()
            ->where('id', $id)
            ->where('conta_id', $contaId)
            ->firstOrFail();

        if ($compra->estado !== 'PENDENTE') {
            return response()->json([
                'status' => 0,
                'sucesso' => false,
                'message' => 'Esta compra já não está pendente.',
                'pode_tentar_novamente' => false,
            ], 422);
        }

        try {

            /*
            * O PagamentoSmsService verifica com
            * lockForUpdate se existe uma transacção
            * INCERTA, PROCESSANDO ou SUCESSO.
            *
            * Só permite uma nova tentativa quando
            * não existe transacção bloqueante.
            */
            $resultado = $pagamentoService->pagarMpesa(
                $compra->id,
                $contaId,
                $dados['telefone'],
                $userId
            );

            $resposta = RespostaPagamentoSms::formatar($resultado);

            $resposta['compra_id'] = $compra->id;

            if ($resposta['sucesso']) {
                $resposta['saldo_sms'] = \App\Models\Carteira::query()
                    ->where('conta_id', $contaId)
                    ->value('saldo_sms');
            }

            return response()->json(
                $resposta,
                RespostaPagamentoSms::httpStatus($resposta)
            );

        } catch (ValidationException $e) {

            throw $e;

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status' => 0,
                'sucesso' => false,
                'compra_id' => $compra->id,
                'pode_tentar_novamente' => false,
                'message' =>
                    'Não foi possível confirmar o resultado. Consulte o estado do pagamento antes de repetir.',
            ], 500);
        }
    }

    public function estado(int $id)
    {
        $contaId = (int) session('conta_id');

        abort_if($contaId <= 0, 403);

        $compra = CompraSms::query()
            ->where('id', $id)
            ->where('conta_id', $contaId)
            ->firstOrFail();

        $transacao = \App\Models\TransacaoPagamento::query()
            ->where('conta_id', $contaId)
            ->where('compra_sms_id', $compra->id)
            ->orderByDesc('id')
            ->first();

        $existeBloqueante = \App\Models\TransacaoPagamento::query()
            ->where('conta_id', $contaId)
            ->where('compra_sms_id', $compra->id)
            ->whereIn('estado', [
                'INICIADA',
                'PROCESSANDO',
                'AGUARDANDO_CONFIRMACAO',
                'INCERTA',
                'SUCESSO',
            ])
            ->exists();

        $paga = $compra->estado === 'PAGA';

        $podeTentarNovamente =
            $compra->estado === 'PENDENTE'
            && !$existeBloqueante
            && (
                $transacao === null
                || in_array($transacao->estado, [
                    'FALHADA',
                    'CANCELADA',
                    'EXPIRADA',
                ], true)
            );

        return response()->json([
            'status' => 1,
            'sucesso' => $paga,
            'compra_id' => $compra->id,
            'estado_compra' => $compra->estado,
            'estado_pagamento' => $transacao?->estado,
            'transacao_id' => $transacao?->id,
            'pagamento_pendente' =>
                !$paga && (
                    $existeBloqueante
                    || $transacao === null
                ),
            'pode_tentar_novamente' => $podeTentarNovamente,
            'message' => $paga
                ? 'Pagamento confirmado e SMS creditados.'
                : (
                    $existeBloqueante
                        ? 'Existe um pagamento em processamento ou por confirmar.'
                        : 'A compra ainda não está paga.'
                ),
        ]);
    }

}