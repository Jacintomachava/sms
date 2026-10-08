<?php

namespace App\Services;

use App\Models\Carteira;
use App\Models\CarteiraMovimento;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class CarteiraService
{
    public function __construct(
        private CarteiraLoteSmsService $loteSmsService
    ) {
    }

    /**
     * Creditar valor na carteira.
     */
    public function creditar(int $carteiraId, float $valor, string $origem, ?string $referencia = null, ?string $descricao = null, ?int $userId = null, ?array $metadata = null): CarteiraMovimento {

        if ($valor <= 0) {
            throw new InvalidArgumentException(
                'O valor do crédito deve ser superior a zero.'
            );
        }

        return DB::transaction(function () use (
            $carteiraId,
            $valor,
            $origem,
            $referencia,
            $descricao,
            $userId,
            $metadata
        ) {
            /*
            |--------------------------------------------------------------------------
            | BLOQUEAR CARTEIRA
            |--------------------------------------------------------------------------
            |
            | lockForUpdate impede duas operações simultâneas
            | de alterarem o mesmo saldo.
            */
            $carteira = Carteira::query()->where('id', $carteiraId)->lockForUpdate()->firstOrFail();

            /*
            |--------------------------------------------------------------------------
            | VERIFICAR ESTADO
            |--------------------------------------------------------------------------
            */
            if ($carteira->estado !== 'ACTIVA') {
                throw new RuntimeException(
                    'A carteira encontra-se bloqueada.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | SALDOS
            |--------------------------------------------------------------------------
            */
            $saldoAnterior = (float) $carteira->saldo;
            $saldoPosterior = round($saldoAnterior + $valor, 2);

            /*
            |--------------------------------------------------------------------------
            | ACTUALIZAR CARTEIRA
            |--------------------------------------------------------------------------
            */
            $carteira->saldo = $saldoPosterior;
            $carteira->save();

            /*
            |--------------------------------------------------------------------------
            | REGISTAR MOVIMENTO
            |--------------------------------------------------------------------------
            */
            return CarteiraMovimento::create([
                'carteira_id' => $carteira->id,
                'tipo' => 'CREDITO',
                'origem' => $origem,
                'valor' => $valor,
                'saldo_anterior' => $saldoAnterior,
                'saldo_posterior' => $saldoPosterior,
                'referencia' => $referencia,
                'descricao' => $descricao,
                'user_id' => $userId,
                'metadata' => $metadata,
            ]);
        });
    }


    /**
     * Debitar valor da carteira.
     */
    public function debitarSms(int $contaId, int $quantidade, string $origem, ?string $referencia = null, ?string $descricao = null, ?int $userId = null, array $metadata = [], ?int $smsId = null): CarteiraMovimento {

        if ($quantidade <= 0) {
            throw new RuntimeException(
                'A quantidade de SMS deve ser superior a zero.'
            );
        }

        return DB::transaction(function () use (
            $contaId,
            $quantidade,
            $origem,
            $referencia,
            $descricao,
            $userId,
            $metadata,
            $smsId
        ) {

            /*
            |--------------------------------------------------------------------------
            | BLOQUEAR CARTEIRA
            |--------------------------------------------------------------------------
            */
            $carteira = Carteira::query()
                ->where('conta_id', $contaId)
                ->lockForUpdate()
                ->first();

            if (!$carteira) {
                throw new RuntimeException(
                    'Carteira não encontrada.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | VALIDAR ESTADO
            |--------------------------------------------------------------------------
            */
            if ($carteira->estado !== 'ACTIVA') {
                throw new RuntimeException(
                    'A carteira encontra-se bloqueada.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | VALIDAR SALDO
            |--------------------------------------------------------------------------
            */
            if ((int) $carteira->saldo_sms < $quantidade) {
                throw new RuntimeException(
                    'Saldo SMS insuficiente.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | CONSUMIR LOTES COMERCIAIS FIFO
            |--------------------------------------------------------------------------
            |
            | Quando o débito pertence a uma SMS concreta, além de reduzir
            | o saldo agregado da carteira, identificamos de quais lotes
            | comerciais vieram os segmentos.
            */
            if ($smsId !== null) {
                $this->loteSmsService->consumir(
                    contaId: $contaId,
                    smsId: $smsId,
                    segmentos: $quantidade
                );
            }

            /*
            |--------------------------------------------------------------------------
            | DEBITAR SALDO AGREGADO
            |--------------------------------------------------------------------------
            */
            $saldoAnterior = (int) $carteira->saldo_sms;
            $saldoPosterior = $saldoAnterior - $quantidade;

            $carteira->update([
                'saldo_sms' => $saldoPosterior,
            ]);

            /*
            |--------------------------------------------------------------------------
            | REGISTAR MOVIMENTO
            |--------------------------------------------------------------------------
            */
            return CarteiraMovimento::create([
                'carteira_id' => $carteira->id,
                'tipo' => 'DEBITO',
                'origem' => $origem,
                'quantidade_sms' => $quantidade,
                'saldo_anterior' => $saldoAnterior,
                'saldo_posterior' => $saldoPosterior,
                'referencia' => $referencia,
                'descricao' => $descricao,
                'user_id' => $userId,
                'metadata' => $metadata,
            ]);
        });
    }
}