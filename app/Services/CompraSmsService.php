<?php

namespace App\Services;

use App\Models\CompraSms;
use App\Models\Conta;
use App\Models\Carteira;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class CompraSmsService
{
    public function __construct(
        private TarifaSmsService $tarifaService
    ) {
    }

    /**
     * Criar uma compra pendente.
     *
     * Não credita a carteira.
     */
    public function criar(
        int $contaId,
        int $quantidade,
        ?int $userId = null
    ): CompraSms {

        if ($quantidade <= 0) {
            throw ValidationException::withMessages([
                'quantidade' => 'A quantidade deve ser superior a zero.',
            ]);
        }

        $conta = Conta::findOrFail($contaId);

        if ($conta->tipo_cobranca !== 'PRE_PAGO') {
            throw ValidationException::withMessages([
                'quantidade' =>
                    'A compra de créditos está disponível apenas para contas pré-pagas.',
            ]);
        }

        $calculo = $this->tarifaService->calcular(
            $contaId,
            $quantidade
        );

        return CompraSms::create([
            'conta_id' => $contaId,
            'tarifa_sms_id' => $calculo['tarifa']->id,
            'quantidade_sms' => $quantidade,
            'preco_unitario' => $calculo['preco_unitario'],
            'valor_total' => $calculo['valor_total'],
            'moeda' => 'MZN',
            'estado' => 'PENDENTE',
            'criado_por' => $userId,
        ]);
    }

    /**
     * Confirmar uma compra e disponibilizar os créditos.
     *
     * Chamar apenas após confirmação de pagamento
     * ou aprovação administrativa autorizada.
     *
     * O bloqueio da compra impede duas confirmações
     * simultâneas de criarem créditos duplicados.
     */
    public function confirmarPagamento(
        int $compraId,
        string $referenciaPagamento,
        ?int $userId = null
    ): CompraSms {

        if (trim($referenciaPagamento) === '') {
            throw new RuntimeException(
                'A referência do pagamento é obrigatória.'
            );
        }

        return DB::transaction(function () use (
            $compraId,
            $referenciaPagamento,
            $userId
        ) {

            /*
            |--------------------------------------------------------------------------
            | 1. BLOQUEAR COMPRA
            |--------------------------------------------------------------------------
            */
            $compra = CompraSms::query()
                ->whereKey($compraId)
                ->lockForUpdate()
                ->firstOrFail();

            /*
            |--------------------------------------------------------------------------
            | 2. IDEMPOTÊNCIA
            |--------------------------------------------------------------------------
            */
            if ($compra->estado === 'PAGO') {
                return $compra;
            }

            if ($compra->estado !== 'PENDENTE') {
                throw new RuntimeException(
                    'Esta compra não pode ser confirmada no estado actual.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 3. BLOQUEAR CARTEIRA
            |--------------------------------------------------------------------------
            */
            $carteira = Carteira::query()
                ->where('conta_id', $compra->conta_id)
                ->lockForUpdate()
                ->first();

            if (!$carteira) {
                throw new RuntimeException(
                    'Carteira da conta não encontrada.'
                );
            }

            if ($carteira->estado !== 'ACTIVA') {
                throw new RuntimeException(
                    'A carteira encontra-se bloqueada.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 4. VALIDAR QUANTIDADE
            |--------------------------------------------------------------------------
            */
            $quantidade = (int) $compra->quantidade_sms;

            if ($quantidade <= 0) {
                throw new RuntimeException(
                    'Quantidade de SMS inválida.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 5. CRIAR LOTE COMERCIAL
            |--------------------------------------------------------------------------
            |
            | Utilizamos os valores gravados na compra,
            | sem recalcular a tarifa.
            |
            */
            DB::table('carteira_lotes_sms')->insert([
                'conta_id' => $compra->conta_id,
                'compra_sms_id' => $compra->id,
                'quantidade_sms' => $quantidade,
                'quantidade_disponivel' => $quantidade,
                'quantidade_reservada' => 0,
                'preco_venda_unitario' => $compra->preco_unitario,
                'valor_total' => $compra->valor_total,
                'moeda' => $compra->moeda,
                'origem' => 'COMPRA',
                'referencia' => $referenciaPagamento,
                'estado' => 'ACTIVO',
                'creditado_em' => now(),
                'criado_por' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | 6. CREDITAR CARTEIRA
            |--------------------------------------------------------------------------
            */
            $saldoAnterior = (int) $carteira->saldo_sms;

            if ($quantidade > PHP_INT_MAX - $saldoAnterior) {
                throw new RuntimeException(
                    'O crédito ultrapassa o limite permitido.'
                );
            }

            $saldoPosterior = $saldoAnterior + $quantidade;

            $carteira->saldo_sms = $saldoPosterior;
            $carteira->save();

            /*
            |--------------------------------------------------------------------------
            | 7. REGISTAR MOVIMENTO
            |--------------------------------------------------------------------------
            */
            DB::table('carteira_movimentos')->insert([
                'carteira_id' => $carteira->id,
                'tipo' => 'CREDITO',
                'origem' => 'COMPRA_SMS',
                'quantidade_sms' => $quantidade,
                'saldo_anterior' => $saldoAnterior,
                'saldo_posterior' => $saldoPosterior,
                'referencia' => $referenciaPagamento,
                'chave_idempotencia' => 'COMPRA_SMS:' . $compra->id,
                'descricao' => 'Crédito de SMS da compra #' . $compra->id,
                'user_id' => $userId,
                'metadata' => json_encode([
                    'compra_sms_id' => $compra->id,
                    'referencia_pagamento' => $referenciaPagamento,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | 8. CONFIRMAR COMPRA
            |--------------------------------------------------------------------------
            */
            $compra->estado = 'PAGO';
            $compra->save();

            return $compra;

        }, 3);
    }
}
