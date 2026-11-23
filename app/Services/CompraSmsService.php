<?php

namespace App\Services;

use App\Models\CompraSms;
use App\Models\Conta;
use App\Models\Carteira;
use App\Models\TransacaoPagamento;
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
     * Criar uma compra pendente de créditos SMS.
     *
     * Nenhum SMS é creditado nesta fase.
     */
    public function criar(
        int $contaId,
        int $quantidade,
        ?int $userId = null
    ): CompraSms {

        if ($quantidade <= 0) {
            throw ValidationException::withMessages([
                'quantidade_sms' =>
                    'A quantidade de SMS deve ser superior a zero.',
            ]);
        }

        $conta = Conta::query()->findOrFail($contaId);

        if ($conta->tipo_cobranca !== 'PRE_PAGO') {
            throw ValidationException::withMessages([
                'conta' =>
                    'A compra de SMS está disponível apenas para contas pré-pagas.',
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
     * Criar ou recuperar uma compra com idempotência.
     *
     * A mesma conta + chave + dados devolve
     * sempre a mesma compra.
     *
     * Este método NÃO inicia o pagamento M-Pesa.
     */
    public function criarViaApi(
        int $contaId,
        int $quantidade,
        string $telefone,
        string $idempotencyKey,
        ?int $userId = null
    ): array {

        $idempotencyKey = trim($idempotencyKey);

        if (
            $idempotencyKey === '' ||
            strlen($idempotencyKey) > 100
        ) {
            throw ValidationException::withMessages([
                'idempotency_key' =>
                    'A chave de idempotência é obrigatória e deve ter até 100 caracteres.',
            ]);
        }

        if ($quantidade <= 0) {
            throw ValidationException::withMessages([
                'quantidade_sms' =>
                    'A quantidade de SMS deve ser superior a zero.',
            ]);
        }

        /*
         * Normalizar telefone para formato local.
         */
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

        /*
         * Hash dos dados comerciais da requisição.
         */
        $hash = hash(
            'sha256',
            json_encode([
                'quantidade_sms' => $quantidade,
                'telefone' => $telefone,
            ], JSON_THROW_ON_ERROR)
        );

        return DB::transaction(function () use (
            $contaId,
            $quantidade,
            $idempotencyKey,
            $hash,
            $userId
        ) {

            /*
            |--------------------------------------------------------------------------
            | 1. BLOQUEAR CONTA
            |--------------------------------------------------------------------------
            |
            | Serializa pedidos concorrentes para a
            | mesma conta.
            */
            $conta = Conta::query()
                ->whereKey($contaId)
                ->lockForUpdate()
                ->firstOrFail();

            /*
            |--------------------------------------------------------------------------
            | 2. PROCURAR COMPRA EXISTENTE
            |--------------------------------------------------------------------------
            */
            $compraExistente = CompraSms::query()
                ->where('conta_id', $contaId)
                ->where('api_idempotency_key', $idempotencyKey)
                ->first();

            if ($compraExistente) {

                if ($compraExistente->api_request_hash !== $hash) {
                    throw ValidationException::withMessages([
                        'idempotency_key' =>
                            'Esta chave já foi utilizada com dados diferentes.',
                    ]);
                }

                return [
                    'compra' => $compraExistente,
                    'criada' => false,
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | 3. VALIDAR CONTA
            |--------------------------------------------------------------------------
            */
            if ($conta->tipo_cobranca !== 'PRE_PAGO') {
                throw ValidationException::withMessages([
                    'conta' =>
                        'Esta conta não permite comprar créditos SMS.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 4. CALCULAR TARIFA
            |--------------------------------------------------------------------------
            */
            $calculo = $this->tarifaService->calcular(
                $contaId,
                $quantidade
            );

            /*
            |--------------------------------------------------------------------------
            | 5. CRIAR COMPRA
            |--------------------------------------------------------------------------
            */
            $compra = CompraSms::create([
                'conta_id' => $contaId,
                'tarifa_sms_id' => $calculo['tarifa']->id,
                'quantidade_sms' => $quantidade,
                'preco_unitario' => $calculo['preco_unitario'],
                'valor_total' => $calculo['valor_total'],
                'moeda' => 'MZN',
                'estado' => 'PENDENTE',
                'criado_por' => $userId,
                'api_idempotency_key' => $idempotencyKey,
                'api_request_hash' => $hash,
            ]);

            return [
                'compra' => $compra,
                'criada' => true,
            ];

        }, 3);
    }

    /**
     * Confirmar manualmente uma compra e creditar SMS.
     *
     * Utilizar apenas após confirmação efectiva
     * do pagamento por um administrador autorizado.
     *
     * Para pagamentos M-Pesa automáticos, utilizar
     * PagamentoSmsService::confirmarPagamento().
     */
    public function confirmarPagamento(
        int $compraId,
        string $referenciaPagamento,
        ?int $userId = null
    ): CompraSms {

        $referenciaPagamento = trim($referenciaPagamento);

        if (
            $referenciaPagamento === '' ||
            strlen($referenciaPagamento) > 100
        ) {
            throw ValidationException::withMessages([
                'referencia_pagamento' =>
                    'A referência de pagamento é inválida.',
            ]);
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
            | 2. VERIFICAR IDEMPOTÊNCIA
            |--------------------------------------------------------------------------
            */
            if ($compra->estado === 'PAGA') {

                $lote = DB::table('carteira_lotes_sms')
                    ->where('compra_sms_id', $compra->id)
                    ->first();

                if (
                    !$lote ||
                    $lote->referencia !== $referenciaPagamento
                ) {
                    throw new RuntimeException(
                        'Esta compra já foi confirmada com outra referência ou não possui lote válido.'
                    );
                }

                return $compra;
            }

            /*
            |--------------------------------------------------------------------------
            | 3. VALIDAR ESTADO
            |--------------------------------------------------------------------------
            */
            if ($compra->estado !== 'PENDENTE') {
                throw ValidationException::withMessages([
                    'compra' =>
                        'Esta compra não pode ser confirmada no estado actual.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 4. BLOQUEAR CONFIRMAÇÃO MANUAL SE
            |    EXISTIR M-PESA POR RESOLVER
            |--------------------------------------------------------------------------
            */
            $existeTentativaBloqueante = TransacaoPagamento::query()
                ->where('compra_sms_id', $compra->id)
                ->where('conta_id', $compra->conta_id)
                ->whereIn('estado', [
                    'INICIADA',
                    'PROCESSANDO',
                    'AGUARDANDO_CONFIRMACAO',
                    'INCERTA',
                    'SUCESSO',
                ])
                ->exists();

            if ($existeTentativaBloqueante) {
                throw ValidationException::withMessages([
                    'pagamento' =>
                        'Existe uma transacção M-Pesa em processamento, confirmada ou pendente de reconciliação. Não é possível confirmar manualmente esta compra.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 5. VALIDAR QUANTIDADE
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
            | 6. VERIFICAR LOTE EXISTENTE
            |--------------------------------------------------------------------------
            */
            $loteExistente = DB::table('carteira_lotes_sms')
                ->where('compra_sms_id', $compra->id)
                ->exists();

            if ($loteExistente) {
                throw new RuntimeException(
                    'Esta compra já possui um lote comercial.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 7. BLOQUEAR CARTEIRA
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
                throw ValidationException::withMessages([
                    'carteira' =>
                        'A carteira encontra-se bloqueada.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 8. CRIAR LOTE COMERCIAL
            |--------------------------------------------------------------------------
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
            | 9. CREDITAR SALDO
            |--------------------------------------------------------------------------
            */
            $saldoAnterior = (int) $carteira->saldo_sms;

            if (
                $quantidade > PHP_INT_MAX - $saldoAnterior
            ) {
                throw new RuntimeException(
                    'A quantidade ultrapassa o limite permitido.'
                );
            }

            $saldoPosterior = $saldoAnterior + $quantidade;

            $carteira->saldo_sms = $saldoPosterior;
            $carteira->save();

            /*
            |--------------------------------------------------------------------------
            | 10. REGISTAR MOVIMENTO
            |--------------------------------------------------------------------------
            */
            DB::table('carteira_movimentos')->insert([
                'carteira_id' => $carteira->id,
                'tipo' => 'CREDITO',
                'origem' => 'COMPRA',
                'quantidade_sms' => $quantidade,
                'saldo_anterior' => $saldoAnterior,
                'saldo_posterior' => $saldoPosterior,
                'referencia' => $referenciaPagamento,
                'chave_idempotencia' => 'COMPRA:' . $compra->id,
                'descricao' =>
                    'Crédito da compra SMS #' . $compra->id,
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
            | 11. FINALIZAR COMPRA
            |--------------------------------------------------------------------------
            */
            $compra->estado = 'PAGA';
            $compra->creditada_em = now();
            $compra->save();

            return $compra;

        }, 3);
    }
}
