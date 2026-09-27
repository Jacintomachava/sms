<?php

namespace App\Services\Pagamentos;

use App\Models\Carteira;
use App\Models\CarteiraMovimento;
use App\Models\CompraSms;
use App\Models\Pagamento;
use App\Models\TransacaoPagamento;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class PagamentoSmsService
{
    public function __construct(private MpesaService $mpesaService) {
    }

    /**
     * Tenta pagar uma compra de SMS via M-Pesa.
     */
    public function pagarMpesa(int $compraId, int $contaId, string $telefone,?int $userId = null): array {

        /*
        |--------------------------------------------------------------------------
        | 1. BUSCAR E VALIDAR A COMPRA
        |--------------------------------------------------------------------------
        */
        $compra = CompraSms::query()->where('id', $compraId)->where('conta_id', $contaId)->firstOrFail();

        if ($compra->estado === 'PAGA') {
            throw ValidationException::withMessages([
                'compra' => 'Esta compra já foi paga.',
            ]);
        }

        if (in_array($compra->estado, ['CANCELADA','EXPIRADA',])) {
            throw ValidationException::withMessages([
                'compra' => 'Esta compra já não pode ser paga.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 2. NORMALIZAR TELEFONE
        |--------------------------------------------------------------------------
        */
        $telefoneLocal = $this->normalizarTelefoneLocal($telefone);

        /*
        |--------------------------------------------------------------------------
        | 3. GERAR REFERÊNCIAS ÚNICAS
        |--------------------------------------------------------------------------
        */
        $invoiceId = $this->gerarInvoiceId($compra);
        $referenceId = $this->gerarReferenceId($invoiceId);

        /*
        |--------------------------------------------------------------------------
        | 4. REGISTAR A TENTATIVA ANTES DE CHAMAR O M-PESA
        |--------------------------------------------------------------------------
        |
        | Isto é fundamental.
        |
        | Mesmo que:
        | - M-Pesa dê timeout
        | - cliente não tenha saldo
        | - cliente cancele
        | - ocorra erro de rede
        |
        | teremos sempre o registo da tentativa.
        */
        $transacao = TransacaoPagamento::create([
            'conta_id' => $contaId,
            'compra_sms_id' => $compra->id,
            'forma_pagamento' => 'MPESA',
            'provider' => 'VODACOM',
            'valor' => $compra->valor_total,
            'moeda' => $compra->moeda ?? 'MZN',
            'telefone' => $telefoneLocal,
            'invoice_id' => $invoiceId,
            'reference_id' => $referenceId,
            'estado' => 'INICIADA',

            'request_payload' => [
                'invoice_id' => $invoiceId,
                'telefone' => '258' . $telefoneLocal,
                'valor' => $compra->valor_total,
                'reference_id' => $referenceId,
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | 5. MARCAR COMO PROCESSANDO
        |--------------------------------------------------------------------------
        */
        $transacao->update([
            'estado' => 'PROCESSANDO',
        ]);

        /*
        |--------------------------------------------------------------------------
        | 6. CHAMAR M-PESA
        |--------------------------------------------------------------------------
        */

        try {

            $resultado = $this->mpesaService->c2b($invoiceId,$telefoneLocal,(string) $compra->valor_total,$referenceId);

        } catch (Throwable $e) {

            /*
             * Segurança adicional.
             *
             * Mesmo que MpesaService lance uma exception,
             * a tentativa não desaparece.
             */
            $transacao->update([
                'estado' => 'FALHADA',
                'erro_tecnico' => $e->getMessage(),
                'processada_em' => now(),
            ]);

            report($e);

            return [
                'sucesso' => false,
                'transacao' => $transacao->fresh(),
                'message' => 'Não foi possível comunicar com o M-Pesa.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 7. ACTUALIZAR A TRANSAÇÃO COM A RESPOSTA
        |--------------------------------------------------------------------------
        */
        $codigo = $resultado['codigo'] ?? null;
        $estado = $this->determinarEstadoTransacao($codigo,(bool) ($resultado['sucesso'] ?? false));

        $transacao->update([
            'provider_codigo' => $codigo,
            'provider_descricao' => $resultado['descricao'] ?? null,
            'provider_transacao_id' => $resultado['transacao_id'] ?? null,
            'response_payload' => $resultado['raw'] ?? null,
            'erro_tecnico' => $resultado['erro_tecnico'] ?? null,
            'estado' => $estado,
            'processada_em' => now(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | 8. SE NÃO HOUVE SUCESSO, TERMINAMOS AQUI
        |--------------------------------------------------------------------------
        |
        | NÃO:
        | - cria pagamento
        | - altera compra
        | - credita carteira
        */
        if ($estado !== 'SUCESSO') {

            return [
                'sucesso' => false,
                'transacao' => $transacao->fresh(),
                'message' => $this->mpesaService->mensagemCodigo($codigo, $resultado['descricao'] ?? null),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 9. CONFIRMAR PAGAMENTO
        |--------------------------------------------------------------------------
        */

        try {

            $pagamento = $this->confirmarPagamento($transacao->id, $userId);

        } catch (Throwable $e) {

            /*
             * ATENÇÃO:
             *
             * Aqui o M-Pesa já respondeu SUCESSO.
             *
             * Portanto NÃO devemos mudar a transação para FALHADA.
             *
             * O dinheiro pode ter sido recebido.
             */

            report($e);

            return [
                'sucesso' => false,
                'transacao' => $transacao->fresh(),
                'message' => 'O pagamento foi recebido pelo M-Pesa, mas ocorreu um erro interno ao creditar os SMS. Contacte o suporte.',
            ];
        }

        return [
            'sucesso' => true,
            'transacao' => $transacao->fresh(),
            'pagamento' => $pagamento,
            'message' => 'Pagamento efectuado e créditos SMS adicionados com sucesso.',
        ];
    }


    /**
     * Confirma um pagamento que teve sucesso no provider.
     *
     * Esta operação é transaccional e idempotente.
     */
    public function confirmarPagamento(int $transacaoId, ?int $userId = null): Pagamento {

        return DB::transaction(function () use (
            $transacaoId,
            $userId
        ) {

            /*
            |--------------------------------------------------------------------------
            | 1. BLOQUEAR TRANSAÇÃO
            |--------------------------------------------------------------------------
            */
            $transacao = TransacaoPagamento::query()->lockForUpdate()->findOrFail($transacaoId);

            if ($transacao->estado !== 'SUCESSO') {

                throw new RuntimeException(
                    'A transação de pagamento não está confirmada.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 2. VERIFICAR SE JÁ GEROU PAGAMENTO
            |--------------------------------------------------------------------------
            |
            | Primeira barreira contra duplicação.
            */
            $pagamentoExistente = Pagamento::query()
                ->where('transacao_pagamento_id', $transacao->id)
                ->first();

            if ($pagamentoExistente) {
                return $pagamentoExistente;
            }

            /*
            |--------------------------------------------------------------------------
            | 3. BLOQUEAR COMPRA
            |--------------------------------------------------------------------------
            */
            $compra = CompraSms::query()->lockForUpdate()->findOrFail($transacao->compra_sms_id);

            /*
            |--------------------------------------------------------------------------
            | 4. VERIFICAR SE A COMPRA JÁ FOI CREDITADA
            |--------------------------------------------------------------------------
            |
            | Segunda barreira.
            |
            | Isto também protege contra duas transações M-Pesa
            | bem-sucedidas para a mesma compra.
            */
            if ($compra->estado === 'PAGA' || $compra->creditada_em !== null) {

                $pagamentoExistente = Pagamento::query()->where('compra_sms_id', $compra->id)->first();

                if ($pagamentoExistente) {
                    return $pagamentoExistente;
                }

                throw new RuntimeException(
                    'A compra já foi creditada, mas o pagamento correspondente não foi encontrado.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 5. VALIDAR VALOR
            |--------------------------------------------------------------------------
            */

            if (
                bccomp(
                    (string) $transacao->valor,
                    (string) $compra->valor_total,
                    2
                ) !== 0
            ) {

                throw new RuntimeException(
                    'O valor pago não corresponde ao valor da compra.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 6. CRIAR PAGAMENTO
            |--------------------------------------------------------------------------
            |
            | Aqui sim existe um pagamento real.
            */

            $pagamento = Pagamento::create([
                'conta_id' => $compra->conta_id,
                'compra_sms_id' => $compra->id,
                'transacao_pagamento_id' => $transacao->id,
                'tipo' => 'COMPRA_SMS',
                'forma_pagamento' => $transacao->forma_pagamento,
                'valor' => $transacao->valor,
                'moeda' => $transacao->moeda,
                'referencia' => $transacao->reference_id,
                'pago_em' => now(),
                /*
                 * M-Pesa foi confirmado automaticamente.
                 *
                 * Não colocamos o utilizador como quem
                 * confirmou o dinheiro.
                 */
                'confirmado_por' => null,
            ]);

            /*
            |--------------------------------------------------------------------------
            | 7. GARANTIR QUE EXISTE CARTEIRA
            |--------------------------------------------------------------------------
            */
            DB::table('carteiras')
                ->insertOrIgnore([
                    'conta_id' => $compra->conta_id,
                    'saldo_sms' => 0,
                    'estado' => 'ACTIVA',
                    'alerta_saldo_baixo_activo' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            /*
            |--------------------------------------------------------------------------
            | 8. BLOQUEAR CARTEIRA
            |--------------------------------------------------------------------------
            */
            $carteira = Carteira::query()
                ->where('conta_id', $compra->conta_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($carteira->estado !== 'ACTIVA') {

                throw new RuntimeException(
                    'A carteira da conta está bloqueada.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 9. CREDITAR SMS
            |--------------------------------------------------------------------------
            */
            $saldoAnterior = (int) $carteira->saldo_sms;
            $quantidade = (int) $compra->quantidade_sms;
            $saldoPosterior = $saldoAnterior + $quantidade;

            $carteira->update([
                'saldo_sms' => $saldoPosterior,
            ]);

            /*
            |--------------------------------------------------------------------------
            | 10. REGISTAR MOVIMENTO
            |--------------------------------------------------------------------------
            */
            CarteiraMovimento::create([
                'carteira_id' => $carteira->id,
                'tipo' => 'CREDITO',
                'origem' => 'COMPRA',
                'quantidade_sms' => $quantidade,
                'saldo_anterior' => $saldoAnterior,
                'saldo_posterior' => $saldoPosterior,
                'referencia' => 'COMPRA-' . $compra->id,
                'descricao' => 'Compra de créditos SMS.',
                /*
                 * Quem iniciou a compra.
                 * Não significa que confirmou o M-Pesa.
                 */
                'user_id' => $userId,

                'metadata' => [
                    'compra_sms_id' => $compra->id,
                    'pagamento_id' => $pagamento->id,
                    'transacao_pagamento_id' => $transacao->id,
                    'forma_pagamento' => $transacao->forma_pagamento,
                    'provider' => $transacao->provider,
                    'provider_transacao_id' => $transacao->provider_transacao_id,
                ],
            ]);

            /*
            |--------------------------------------------------------------------------
            | 11. FINALIZAR COMPRA
            |--------------------------------------------------------------------------
            */
            $compra->update([
                'estado' => 'PAGA',
                'creditada_em' => now(),
            ]);

            return $pagamento;
        });
    }

    /**
     * Determina o estado interno com base na resposta M-Pesa.
     */
    private function determinarEstadoTransacao(?string $codigo, bool $sucesso): string {

        if ($sucesso && $codigo === 'INS-0') {
            return 'SUCESSO';
        }

        return match ($codigo) {

            'INS-5' => 'CANCELADA',
            'INS-9' => 'EXPIRADA',
            default => 'FALHADA',
        };
    }

    /**
     * Gera Invoice ID para a tentativa.
     */
    private function gerarInvoiceId(CompraSms $compra): string {

        return 'SMS' .$compra->id .now()->format('His');
    }

    /**
     * Cada tentativa tem referência diferente.
     */
    private function gerarReferenceId(string $invoiceId): string {

        $referencia = $invoiceId .random_int(1000, 9999);

        if (strlen($referencia) > 20) {
            throw new \RuntimeException(
                'A referência M-Pesa não pode exceder 20 caracteres.'
            );
        }

        return $referencia;
    }

    /**
     * Guarda localmente 84xxxxxxx.
     *
     * MpesaService adiciona 258.
     */
    private function normalizarTelefoneLocal(
        string $telefone
    ): string {

        $telefone =
            preg_replace(
                '/\D+/',
                '',
                $telefone
            );

        if (
            strlen($telefone) === 12 &&
            str_starts_with(
                $telefone,
                '258'
            )
        ) {

            $telefone =
                substr(
                    $telefone,
                    3
                );
        }

        if (!preg_match(
            '/^[0-9]{9}$/',
            $telefone
        )) {

            throw ValidationException::withMessages([
                'telefone' =>
                    'O número de telefone é inválido.',
            ]);
        }

        return $telefone;
    }
}