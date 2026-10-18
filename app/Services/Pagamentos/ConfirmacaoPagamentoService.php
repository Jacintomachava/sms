<?php

namespace App\Services\Pagamentos;

use App\Models\CompraSms;
use App\Models\Pagamento;
use App\Models\Carteira;
use App\Models\CarteiraLoteSms;
use App\Models\CarteiraMovimento;
use App\Models\TransacaoPagamento;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ConfirmacaoPagamentoService
{
    public function confirmar(
        int $transacaoId,
        ?int $userId = null
    ): Pagamento {

        return DB::transaction(function () use (
            $transacaoId,
            $userId
        ) {
            /*
             * 1. Bloquear transacção
             */
            $transacao = TransacaoPagamento::query()
                ->lockForUpdate()
                ->findOrFail($transacaoId);

            if ($transacao->estado !== 'SUCESSO') {
                throw new RuntimeException(
                    'A transacção ainda não foi confirmada.'
                );
            }

            /*
             * 2. Bloquear compra
             */
            $compra = CompraSms::query()
                ->lockForUpdate()
                ->findOrFail($transacao->compra_sms_id);

            if ($compra->conta_id !== $transacao->conta_id) {
                throw new RuntimeException(
                    'A transacção não pertence à conta da compra.'
                );
            }

            if (bccomp(
                (string) $transacao->valor,
                (string) $compra->valor_total,
                2
            ) !== 0) {
                throw new RuntimeException(
                    'O valor pago não corresponde à compra.'
                );
            }

            /*
             * 3. Idempotência
             *
             * Se a compra já foi creditada,
             * devolver o pagamento existente.
             */
            $pagamento = Pagamento::query()
                ->where('compra_sms_id', $compra->id)
                ->first();

            if ($compra->creditada_em !== null) {
                if (!$pagamento) {
                    throw new RuntimeException(
                        'Compra creditada sem pagamento associado.'
                    );
                }

                return $pagamento;
            }

            /*
             * 4. Registar pagamento, se necessário.
             */
            if (!$pagamento) {
                $pagamento = Pagamento::create([
                    'conta_id' => $compra->conta_id,
                    'compra_sms_id' => $compra->id,
                    'transacao_pagamento_id' => $transacao->id,
                    'tipo' => 'COMPRA_SMS',
                    'forma_pagamento' => $transacao->forma_pagamento,
                    'valor' => $transacao->valor,
                    'moeda' => $transacao->moeda,
                    'referencia' =>
                        $transacao->reference_id
                        ?? $transacao->referencia_externa,
                    'pago_em' =>
                        $transacao->confirmada_em
                        ?? now(),
                    'confirmado_por' => $userId,
                ]);
            }

            /*
             * 5. Garantir carteira
             */
            DB::table('carteiras')->insertOrIgnore([
                'conta_id' => $compra->conta_id,
                'saldo_sms' => 0,
                'saldo_reservado_sms' => 0,
                'estado' => 'ACTIVA',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $carteira = Carteira::query()
                ->where('conta_id', $compra->conta_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($carteira->estado !== 'ACTIVA') {
                throw new RuntimeException(
                    'A carteira está bloqueada.'
                );
            }

            /*
             * 6. Criar lote comercial
             */
            $lote = CarteiraLoteSms::query()
                ->where('compra_sms_id', $compra->id)
                ->first();

            if ($lote) {
                throw new RuntimeException(
                    'Foi encontrado um lote de compra sem ' .
                    'a respectiva confirmação de crédito. ' .
                    'É necessária reconciliação.'
                );
            }

            $lote = CarteiraLoteSms::create([
                'conta_id' => $compra->conta_id,
                'compra_sms_id' => $compra->id,
                'quantidade_sms' => $compra->quantidade_sms,
                'quantidade_disponivel' => $compra->quantidade_sms,
                'quantidade_reservada' => 0,
                'preco_venda_unitario' => $compra->preco_unitario,
                'valor_total' => $compra->valor_total,
                'moeda' => $compra->moeda,
                'origem' => 'COMPRA',
                'referencia' => 'COMPRA-' . $compra->id,
                'estado' => 'ACTIVO',
                'creditado_em' => now(),
                'criado_por' => $userId,
            ]);

            /*
             * 7. Actualizar saldo
             */
            $quantidade = (int) $compra->quantidade_sms;

            $saldoAnterior = (int) $carteira->saldo_sms;
            $saldoPosterior = $saldoAnterior + $quantidade;

            $carteira->update([
                'saldo_sms' => $saldoPosterior,
            ]);

            /*
             * 8. Registar movimento
             */
            CarteiraMovimento::create([
                'carteira_id' => $carteira->id,
                'tipo' => 'CREDITO',
                'origem' => 'COMPRA',
                'quantidade_sms' => $quantidade,
                'saldo_anterior' => $saldoAnterior,
                'saldo_posterior' => $saldoPosterior,
                'chave_idempotencia' =>
                    'COMPRA_SMS:' . $compra->id . ':CREDITO',
                'referencia' => 'COMPRA-' . $compra->id,
                'descricao' => 'Compra de créditos SMS.',
                'user_id' => $userId,
                'metadata' => [
                    'compra_sms_id' => $compra->id,
                    'pagamento_id' => $pagamento->id,
                    'transacao_pagamento_id' => $transacao->id,
                    'lote_id' => $lote->id,
                    'valor_total' => (string) $compra->valor_total,
                ],
            ]);

            /*
             * 9. Finalizar compra
             */
            $compra->update([
                'estado' => 'PAGA',
                'paga_em' => $compra->paga_em
                    ?? $pagamento->pago_em,
                'creditada_em' => now(),
            ]);

            return $pagamento;
        }, 3);
    }
}
