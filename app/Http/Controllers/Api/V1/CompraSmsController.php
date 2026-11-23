<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CompraSms;
use App\Services\CompraSmsService;
use App\Services\TarifaSmsService;
use App\Services\Pagamentos\PagamentoSmsService;
use App\Support\RespostaPagamentoSms;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CompraSmsController extends Controller
{
    private function contaId(Request $request): int
    {
        $contaId = (int) $request->attributes->get('conta_id');

        if ($contaId <= 0) {
            abort(401, 'Conta não autenticada.');
        }

        return $contaId;
    }

    /**
     * Simular o custo da compra.
     */
    public function quote(
        Request $request,
        TarifaSmsService $tarifaService
    ) {
        $dados = $request->validate([
            'quantidade_sms' => 'required|integer|min:1',
        ]);

        $calculo = $tarifaService->calcular(
            $this->contaId($request),
            (int) $dados['quantidade_sms']
        );

        return response()->json([
            'status' => 1,
            'message' => 'Cotação calculada com sucesso.',
            'data' => [
                'quantidade_sms' => $calculo['quantidade'],
                'preco_unitario' => $calculo['preco_unitario'],
                'valor_total' => $calculo['valor_total'],
                'moeda' => 'MZN',
                'tarifa_id' => $calculo['tarifa']->id,
            ],
        ]);
    }

    /**
     * Criar compra e iniciar pagamento M-Pesa.
     */
    public function store(
        Request $request,
        CompraSmsService $compraService,
        PagamentoSmsService $pagamentoService
    ) {
        $dados = $request->validate([
            'quantidade_sms' => 'required|integer|min:1',
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
                    'O cabeçalho Idempotency-Key é obrigatório e deve ter até 100 caracteres.',
            ]);
        }

        $contaId = $this->contaId($request);

        $criacao = $compraService->criarViaApi(
            $contaId,
            (int) $dados['quantidade_sms'],
            $dados['telefone'],
            $chave
        );

        $compra = $criacao['compra'];

        /*
         * A mesma requisição não pode iniciar
         * um segundo débito M-Pesa.
         */
        if (!$criacao['criada']) {
            return response()->json([
                'status' => 1,
                'message' => 'Compra já registada anteriormente.',
                'data' => [
                    'compra_id' => $compra->id,
                    'estado' => $compra->estado,
                    'pagamento_reiniciado' => false,
                ],
            ]);
        }

        $resultado = $pagamentoService->pagarMpesa(
            $compra->id,
            $contaId,
            $dados['telefone'],
            null
        );

        $resposta = RespostaPagamentoSms::formatar($resultado);

        $resposta['compra_id'] = $compra->id;

        return response()->json(
            $resposta,
            RespostaPagamentoSms::httpStatus($resposta)
        );
    }

    /**
     * Consultar uma compra.
     */
    public function show(Request $request, int $id)
    {
        $contaId = $this->contaId($request);

        $compra = CompraSms::query()
            ->where('conta_id', $contaId)
            ->findOrFail($id);

        $transacao = \App\Models\TransacaoPagamento::query()
            ->where('conta_id', $contaId)
            ->where('compra_sms_id', $compra->id)
            ->orderByDesc('id')
            ->first();

        return response()->json([
            'status' => 1,
            'data' => [
                'compra_id' => $compra->id,
                'estado_compra' => $compra->estado,
                'quantidade_sms' => $compra->quantidade_sms,
                'preco_unitario' => $compra->preco_unitario,
                'valor_total' => $compra->valor_total,
                'moeda' => $compra->moeda,
                'transacao_id' => $transacao?->id,
                'estado_pagamento' => $transacao?->estado,
            ],
        ]);
    }

    /**
     * Tentar pagar uma compra pendente.
     */
    public function pay(
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

        $contaId = $this->contaId($request);

        $compra = CompraSms::query()
            ->where('conta_id', $contaId)
            ->findOrFail($id);

        $resultado = $pagamentoService->pagarMpesa(
            $compra->id,
            $contaId,
            $dados['telefone'],
            null
        );

        $resposta = RespostaPagamentoSms::formatar($resultado);

        $resposta['compra_id'] = $compra->id;

        return response()->json(
            $resposta,
            RespostaPagamentoSms::httpStatus($resposta)
        );
    }
}
