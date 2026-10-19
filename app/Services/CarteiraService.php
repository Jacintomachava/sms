<?php

namespace App\Services;

use App\Models\Carteira;
use App\Models\CarteiraMovimento;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class CarteiraService
{
    /**
     * Consultar os saldos de uma carteira.
     */
    public function consultarSaldo(int $contaId): array
    {
        $carteira = Carteira::query()
            ->where('conta_id', $contaId)
            ->firstOrFail();

        $saldo = (int) $carteira->saldo_sms;
        $reservado = (int) $carteira->saldo_reservado_sms;

        return [
            'conta_id' => $contaId,
            'carteira_id' => $carteira->id,
            'saldo_sms' => $saldo,
            'saldo_reservado_sms' => $reservado,
            'saldo_livre_sms' => max(
                0,
                $saldo - $reservado
            ),
        ];
    }

    /**
     * Creditar SMS na carteira.
     *
     * IMPORTANTE:
     * Este método altera apenas a carteira e
     * regista o movimento.
     *
     * Para uma compra de SMS, a criação do lote
     * comercial deve ocorrer na mesma transacção
     * que chama este método.
     */
    public function creditar(
        int $carteiraId,
        int $quantidade,
        string $origem,
        ?string $referencia = null,
        ?string $descricao = null,
        ?int $userId = null,
        ?array $metadata = null
    ): CarteiraMovimento {

        if ($quantidade <= 0) {
            throw new InvalidArgumentException(
                'A quantidade de SMS deve ser superior a zero.'
            );
        }

        return DB::transaction(function () use (
            $carteiraId,
            $quantidade,
            $origem,
            $referencia,
            $descricao,
            $userId,
            $metadata
        ) {

            /*
            |--------------------------------------------------------------------------
            | 1. BLOQUEAR CARTEIRA
            |--------------------------------------------------------------------------
            */
            $carteira = Carteira::query()
                ->whereKey($carteiraId)
                ->lockForUpdate()
                ->firstOrFail();

            /*
            |--------------------------------------------------------------------------
            | 2. VALIDAR ESTADO
            |--------------------------------------------------------------------------
            */
            if ($carteira->estado !== 'ACTIVA') {
                throw new RuntimeException(
                    'A carteira encontra-se bloqueada.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 3. ACTUALIZAR SALDO
            |--------------------------------------------------------------------------
            */
            $saldoAnterior = (int) $carteira->saldo_sms;

            if (
                $quantidade >
                PHP_INT_MAX - $saldoAnterior
            ) {
                throw new RuntimeException(
                    'O crédito ultrapassa o limite permitido.'
                );
            }

            $saldoPosterior = $saldoAnterior + $quantidade;

            $carteira->saldo_sms = $saldoPosterior;
            $carteira->save();

            /*
            |--------------------------------------------------------------------------
            | 4. REGISTAR MOVIMENTO
            |--------------------------------------------------------------------------
            */
            return CarteiraMovimento::create([
                'carteira_id' => $carteira->id,
                'tipo' => 'CREDITO',
                'origem' => $origem,
                'quantidade_sms' => $quantidade,
                'saldo_anterior' => $saldoAnterior,
                'saldo_posterior' => $saldoPosterior,
                'referencia' => $referencia,
                'descricao' => $descricao,
                'user_id' => $userId,
                'metadata' => $metadata,
            ]);

        }, 3);
    }

    /**
     * Débito directo desactivado.
     *
     * O consumo financeiro de SMS deve ser feito
     * exclusivamente pelo SmsConfirmacaoService.
     *
     * Mantemos a assinatura do método para
     * identificar chamadas antigas e evitar
     * débitos que contornem as reservas.
     */
    public function debitarSms(
        int $contaId,
        int $quantidade,
        string $origem,
        ?string $referencia = null,
        ?string $descricao = null,
        ?int $userId = null,
        array $metadata = [],
        ?int $smsId = null
    ): CarteiraMovimento {

        throw new RuntimeException(
            'Débito directo de SMS desactivado. ' .
            'Utilize SmsReservaService para reservar ' .
            'e SmsConfirmacaoService para confirmar ' .
            'o consumo após a aceitação SMPP.'
        );
    }
}
