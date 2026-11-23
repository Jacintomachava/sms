<?php

namespace App\Services\Pagamentos;

use App\Models\Carteira;
use App\Models\CarteiraMovimento;
use App\Models\CompraSms;
use App\Models\Pagamento;
use App\Models\CarteiraLoteSms;
use App\Models\TransacaoPagamento;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class PagamentoSmsService
{
    public function __construct(private MpesaService $mpesaService, private ConfirmacaoPagamentoService $confirmacaoService) {
    }

    /**
     * Tenta pagar uma compra de SMS via M-Pesa.
     */
    public function pagarMpesa(
        int $compraId,
        int $contaId,
        string $telefone,
        ?int $userId = null
    ): array {

        $telefoneLocal = $this->normalizarTelefoneLocal($telefone);

        /*
        |--------------------------------------------------------------------------
        | 1. RESERVAR TENTATIVA COM BLOQUEIO
        |--------------------------------------------------------------------------
        */
        $transacao = DB::transaction(function () use (
            $compraId,
            $contaId,
            $telefoneLocal
        ) {

            $compra = CompraSms::query()
                ->where('id', $compraId)
                ->where('conta_id', $contaId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($compra->estado !== 'PENDENTE') {
                throw ValidationException::withMessages([
                    'compra' => 'Esta compra já não permite pagamentos.',
                ]);
            }

            /*
            * Bloquear qualquer tentativa não resolvida,
            * mesmo que não seja a última.
            */
            $existeBloqueante = TransacaoPagamento::query()
                ->where('compra_sms_id', $compraId)
                ->where('conta_id', $contaId)
                ->whereIn('estado', [
                    'INICIADA',
                    'PROCESSANDO',
                    'AGUARDANDO_CONFIRMACAO',
                    'INCERTA',
                    'SUCESSO',
                ])
                ->exists();

            if ($existeBloqueante) {
                throw ValidationException::withMessages([
                    'pagamento' =>
                        'Existe um pagamento em processamento, confirmado ou pendente de reconciliação.',
                ]);
            }

            $invoiceId = $this->gerarInvoiceId($compra);
            $referenceId = $this->gerarReferenceId($invoiceId);

            return TransacaoPagamento::create([
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

        }, 3);

        /*
        |--------------------------------------------------------------------------
        | 2. INICIAR COMUNICAÇÃO COM O M-PESA
        |--------------------------------------------------------------------------
        */
        $transacao->update([
            'estado' => 'PROCESSANDO',
        ]);

        try {

            $resultado = $this->mpesaService->c2b(
                $transacao->invoice_id,
                $telefoneLocal,
                (string) $transacao->valor,
                $transacao->reference_id
            );
            

        } catch (Throwable $e) {

            report($e);

            /*
            * Não sabemos se o operador recebeu
            * ou executou a cobrança.
            */
            $resultado = [
                'sucesso' => false,
                'resultado_confirmado' => false,
                'codigo' => null,
                'descricao' => 'Resultado do M-Pesa desconhecido.',
                'transacao_id' => null,
                'raw' => null,
                'erro_tecnico' => $e->getMessage(),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 3. CLASSIFICAR RESULTADO
        |--------------------------------------------------------------------------
        */
        $codigo = $resultado['codigo'] ?? null;

        $estado = $this->determinarEstadoTransacao(
            $resultado['codigo'] ?? null,
            (bool) ($resultado['sucesso'] ?? false),
            (bool) ($resultado['resultado_confirmado'] ?? false)
        );

        /*
        |--------------------------------------------------------------------------
        | 4. REGISTAR RESPOSTA
        |--------------------------------------------------------------------------
        */
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
        | 5. PAGAMENTO NÃO CONFIRMADO
        |--------------------------------------------------------------------------
        */
        if ($estado !== 'SUCESSO') {

            $podeTentarNovamente = in_array(
                $estado,
                ['FALHADA', 'CANCELADA', 'EXPIRADA'],
                true
            );

            $mensagem = $podeTentarNovamente
                ? $this->mpesaService->mensagemCodigo(
                    $codigo,
                    $resultado['descricao'] ?? null
                )
                : 'O resultado do pagamento ainda não foi confirmado. Aguarde a reconciliação antes de tentar novamente.';

            return [
                'sucesso' => false,
                'transacao' => $transacao->fresh(),
                'estado_pagamento' => $estado,
                'pode_tentar_novamente' => $podeTentarNovamente,
                'message' => $mensagem,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 6. PAGAMENTO CONFIRMADO PELO M-PESA
        |--------------------------------------------------------------------------
        */
        try {

            $pagamento = $this->confirmarPagamento(
                $transacao->id,
                $userId
            );

        } catch (Throwable $e) {

            report($e);

            /*
            * O M-Pesa confirmou o débito.
            * Nunca repetir a cobrança para corrigir
            * um erro interno de crédito.
            */
            return [
                'sucesso' => false,
                'transacao' => $transacao->fresh(),
                'estado_pagamento' => 'SUCESSO',
                'pode_tentar_novamente' => false,
                'requer_reconciliacao' => true,
                'message' =>
                    'O M-Pesa confirmou o pagamento, mas ocorreu um erro ao creditar os SMS. Contacte o suporte.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 7. COMPRA FINALIZADA
        |--------------------------------------------------------------------------
        */
        return [
            'sucesso' => true,
            'transacao' => $transacao->fresh(),
            'pagamento' => $pagamento,
            'estado_pagamento' => 'SUCESSO',
            'pode_tentar_novamente' => false,
            'message' =>
                'Pagamento efectuado e créditos SMS adicionados com sucesso.',
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
            | 9. CRIAR LOTE COMERCIAL DOS CRÉDITOS
            |--------------------------------------------------------------------------
            |
            | O lote guarda o snapshot comercial da compra.
            |
            | A compra pode gerar apenas um lote, garantido também
            | pelo UNIQUE(compra_sms_id).
            |
            */
            $lote = CarteiraLoteSms::create([
                'conta_id' => $compra->conta_id,
                'compra_sms_id' => $compra->id,
                'quantidade_sms' => $compra->quantidade_sms,
                'quantidade_disponivel' => $compra->quantidade_sms,
                'preco_venda_unitario' => $compra->preco_unitario,
                'valor_total' => $compra->valor_total,
                'moeda' => $compra->moeda ?? 'MZN',
                'origem' => 'COMPRA',
                'referencia' => 'COMPRA-' . $compra->id,
                'estado' => 'ACTIVO',
                'creditado_em' => now(),
                'criado_por' => $userId,
            ]);

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
                'chave_idempotencia' => 'COMPRA-' . $compra->id,
                'descricao' => 'Compra de créditos SMS.',
                /*
                 * Quem iniciou a compra.
                 * Não significa que confirmou o M-Pesa.
                 */
                'user_id' => $userId,

                'metadata' => [
                    'compra_sms_id' => $compra->id,
                    'carteira_lote_sms_id' => $lote->id,
                    'pagamento_id' => $pagamento->id,
                    'transacao_pagamento_id' => $transacao->id,
                    'forma_pagamento' => $transacao->forma_pagamento,
                    'provider' => $transacao->provider,
                    'provider_transacao_id' => $transacao->provider_transacao_id,
                    'preco_unitario' => (string) $compra->preco_unitario,
                    'valor_total' => (string) $compra->valor_total,
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
    private function determinarEstadoTransacao(
        ?string $codigo,
        bool $sucesso,
        bool $resultadoConfirmado = false
    ): string {

        if ($sucesso && $codigo === 'INS-0') {
            return 'SUCESSO';
        }

        if (!$resultadoConfirmado) {
            return 'INCERTA';
        }

        return match ($codigo) {
            'INS-5' => 'CANCELADA',
            'INS-6' => 'FALHADA',
            default => 'INCERTA',
        };
    }

    /**
     * Gera Invoice ID para a tentativa.
     */
    private function gerarInvoiceId(CompraSms $compra): string
    {
        return 'S'
            . $compra->id
            . now()->format('His')
            . random_int(100, 999);
    }

    private function gerarReferenceId(string $invoiceId): string
    {
        $referencia = $invoiceId . random_int(1000, 9999);

        if (strlen($referencia) > 20) {
            throw new RuntimeException(
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
    private function normalizarTelefoneLocal(string $telefone): string
    {
        $telefone = preg_replace('/\D+/', '', $telefone);

        if (
            strlen($telefone) === 12 &&
            str_starts_with($telefone, '258')
        ) {
            $telefone = substr($telefone, 3);
        }

        if (!preg_match('/^(84|85)[0-9]{7}$/', $telefone)) {
            throw ValidationException::withMessages([
                'telefone' =>
                    'Número M-Pesa inválido. Utilize 84XXXXXXX ou 85XXXXXXX.',
            ]);
        }

        return $telefone;
    }

    /**
     * Verifica se uma compra pode iniciar outra
     * tentativa de pagamento M-Pesa.
     */
    private function validarNovaTentativaMpesa(
        int $compraId,
        int $contaId
    ): void {

        $compra = CompraSms::query()
            ->where('id', $compraId)
            ->where('conta_id', $contaId)
            ->firstOrFail();

        if ($compra->estado !== 'PENDENTE') {
            throw ValidationException::withMessages([
                'compra' => 'Esta compra já não permite pagamentos.',
            ]);
        }

        $ultimaTransacao = TransacaoPagamento::query()
            ->where('compra_sms_id', $compraId)
            ->where('conta_id', $contaId)
            ->orderByDesc('id')
            ->first();

        if (!$ultimaTransacao) {
            return;
        }

        if (in_array(
            $ultimaTransacao->estado,
            [
                'INICIADA',
                'PROCESSANDO',
                'AGUARDANDO_CONFIRMACAO',
                'SUCESSO',
            ],
            true
        )) {
            throw ValidationException::withMessages([
                'pagamento' =>
                    'Existe um pagamento em processamento ou por confirmar. Aguarde a confirmação antes de tentar novamente.',
            ]);
        }

        if (!in_array(
            $ultimaTransacao->estado,
            ['FALHADA', 'CANCELADA', 'EXPIRADA'],
            true
        )) {
            throw ValidationException::withMessages([
                'pagamento' =>
                    'O estado da última tentativa não permite iniciar outro pagamento.',
            ]);
        }
    }
}