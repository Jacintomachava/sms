<?php

namespace App\Services;

use App\Models\CompraStockSms;
use App\Models\SmsConsumoStock;
use App\Models\StockSmsMovimento;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockSmsService
{
    /**
     * Stock físico total disponível.
     */
    public function saldoDisponivel(): int
    {
        return (int) CompraStockSms::query()->where('estado', 'ACTIVO')->sum('quantidade_disponivel');
    }

    /**
     * Verifica se existe stock suficiente.
     */
    public function temStock(int $quantidade): bool
    {
        if ($quantidade <= 0) {
            return false;
        }

        return $this->saldoDisponivel() >= $quantidade;
    }

    /**
     * Ajuste positivo de um lote.
     */
    public function ajustarEntrada(int $loteId, int $quantidade, string $motivo, ?int $userId = null): CompraStockSms {

        if ($quantidade <= 0) {
            throw new RuntimeException(
                'A quantidade do ajuste deve ser superior a zero.'
            );
        }

        $motivo = trim($motivo);

        if ($motivo === '') {
            throw new RuntimeException(
                'O motivo do ajuste é obrigatório.'
            );
        }

        return DB::transaction(function () use (
            $loteId,
            $quantidade,
            $motivo,
            $userId
        ) {

            /*
             * lockForUpdate impede dois ajustes simultâneos
             * de trabalharem sobre o mesmo saldo.
             */
            $lote = CompraStockSms::query()->whereKey($loteId)->lockForUpdate()->firstOrFail();

            if ($lote->estado === 'CANCELADO') {
                throw new RuntimeException(
                    'Não é possível ajustar um lote cancelado.'
                );
            }

            $saldoAnterior = (int) $lote->quantidade_disponivel;
            $saldoPosterior = $saldoAnterior + $quantidade;
            $lote->quantidade_disponivel = $saldoPosterior;

            /*
             * Se estava ESGOTADO e recebeu stock novamente,
             * volta a ACTIVO.
             */
            if ($lote->estado === 'ESGOTADO') {
                $lote->estado = 'ACTIVO';
            }

            $lote->save();

            StockSmsMovimento::create([
                'compra_stock_sms_id' => $lote->id,
                'tipo' => 'AJUSTE_ENTRADA',
                'quantidade_sms' => $quantidade,
                'saldo_anterior' => $saldoAnterior,
                'saldo_posterior' => $saldoPosterior,
                'referencia' => null,
                'descricao' => $motivo,

                'metadata' => [
                    'tipo_ajuste' => 'ENTRADA',
                ],

                'user_id' => $userId,
            ]);

            return $lote->fresh();
        });
    }

    /**
     * Ajuste negativo de um lote.
     */
    public function ajustarSaida(int $loteId, int $quantidade, string $motivo, ?int $userId = null): CompraStockSms {

        if ($quantidade <= 0) {
            throw new RuntimeException(
                'A quantidade do ajuste deve ser superior a zero.'
            );
        }

        $motivo = trim($motivo);

        if ($motivo === '') {
            throw new RuntimeException(
                'O motivo do ajuste é obrigatório.'
            );
        }

        return DB::transaction(function () use (
            $loteId,
            $quantidade,
            $motivo,
            $userId
        ) {

            $lote = CompraStockSms::query()->whereKey($loteId)->lockForUpdate()->firstOrFail();

            if ($lote->estado === 'CANCELADO') {
                throw new RuntimeException(
                    'Não é possível ajustar um lote cancelado.'
                );
            }

            $saldoAnterior = (int) $lote->quantidade_disponivel;

            if ($saldoAnterior < $quantidade) {
                throw new RuntimeException(
                    'O lote não possui stock suficiente para este ajuste.'
                );
            }

            $saldoPosterior = $saldoAnterior - $quantidade;
            $lote->quantidade_disponivel = $saldoPosterior;

            if ($saldoPosterior === 0) {
                $lote->estado = 'ESGOTADO';
            }

            $lote->save();

            StockSmsMovimento::create([
                'compra_stock_sms_id' => $lote->id,
                'tipo' => 'AJUSTE_SAIDA',
                'quantidade_sms' => $quantidade,
                'saldo_anterior' => $saldoAnterior,
                'saldo_posterior' => $saldoPosterior,
                'referencia' => null,
                'descricao' => $motivo,

                'metadata' => [
                    'tipo_ajuste' => 'SAIDA',
                ],

                'user_id' => $userId,
            ]);

            return $lote->fresh();
        });
    }

    public function consumir(int $smsId, int $segmentos,?int $userId = null): array {

        return DB::transaction(function () use (
            $smsId,
            $segmentos,
            $userId
        ) {

            return $this->consumirDentroDaTransacao(
                smsId: $smsId,
                segmentos: $segmentos,
                userId: $userId
            );
        });
    }

    public function consumirDentroDaTransacao(int $smsId, int $segmentos, ?int $userId = null): array {

        if ($segmentos <= 0) {
            throw new RuntimeException(
                'A quantidade de segmentos deve ser superior a zero.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | IDEMPOTÊNCIA
        |--------------------------------------------------------------------------
        */
        $jaConsumido = SmsConsumoStock::query()->where('sms_id', $smsId)->exists();

        if ($jaConsumido) {
            throw new RuntimeException(
                'O stock desta SMS já foi consumido.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | LOTES FIFO
        |--------------------------------------------------------------------------
        */
        $lotes = CompraStockSms::query()
            ->where('estado', 'ACTIVO')
            ->where('quantidade_disponivel', '>', 0)
            ->orderBy('data_compra')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $stockDisponivel = (int) $lotes->sum('quantidade_disponivel');

        if ($stockDisponivel < $segmentos) {

            throw new RuntimeException(
                'Stock SMS insuficiente.'
            );
        }

        $restante = $segmentos;
        $custoTotal = '0.0000';
        $alocacoes = [];

        foreach ($lotes as $lote) {

            if ($restante <= 0) {
                break;
            }

            $saldoAnterior = (int) $lote->quantidade_disponivel;
            $consumir = min($saldoAnterior,$restante);
            $saldoPosterior = $saldoAnterior - $consumir;
            $custoLote = bcmul((string) $consumir, (string) $lote->preco_unitario, 4);

            /*
            |--------------------------------------------------------------------------
            | ACTUALIZAR LOTE
            |--------------------------------------------------------------------------
            */
            $lote->quantidade_disponivel = $saldoPosterior;

            if ($saldoPosterior === 0) {
                $lote->estado = 'ESGOTADO';
            }

            $lote->save();

            /*
            |--------------------------------------------------------------------------
            | ALOCAÇÃO DA SMS
            |--------------------------------------------------------------------------
            */
            SmsConsumoStock::create([
                'sms_id' => $smsId,
                'compra_stock_sms_id' => $lote->id,
                'segmentos' => $consumir,
                'preco_compra_unitario' => $lote->preco_unitario,
                'custo_total' => $custoLote,
            ]);

            /*
            |--------------------------------------------------------------------------
            | MOVIMENTO
            |--------------------------------------------------------------------------
            */
            StockSmsMovimento::create([
                'compra_stock_sms_id' => $lote->id,
                'tipo' => 'CONSUMO',
                'quantidade_sms' => $consumir,
                'saldo_anterior' => $saldoAnterior,
                'saldo_posterior' => $saldoPosterior,
                'referencia' => 'SMS-' . $smsId,
                'descricao' => 'Consumo de stock pela SMS #' . $smsId,

                'metadata' => [
                    'sms_id' => $smsId,
                    'segmentos' => $consumir,
                    'preco_unitario' => $lote->preco_unitario,
                    'custo' => $custoLote,
                ],

                'user_id' => $userId,
            ]);

            $alocacoes[] = [
                'lote_id' => $lote->id,
                'segmentos' => $consumir,
                'preco_unitario' => (string) $lote->preco_unitario,
                'custo' => $custoLote,
            ];

            $custoTotal = bcadd($custoTotal, $custoLote, 4);

            $restante -= $consumir;
        }

        return [
            'sms_id' => $smsId,
            'segmentos' => $segmentos,
            'custo_total' => $custoTotal,
            'alocacoes' => $alocacoes,
        ];
    }
}