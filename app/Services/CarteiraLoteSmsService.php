<?php

namespace App\Services;

use App\Models\CarteiraLoteSms;
use App\Models\SmsConsumoCredito;
use RuntimeException;

class CarteiraLoteSmsService
{
    public function consumir(int $contaId, int $smsId, int $segmentos): array {

        if ($segmentos <= 0) {
            throw new RuntimeException(
                'Quantidade de segmentos inválida.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | IDEMPOTÊNCIA
        |--------------------------------------------------------------------------
        */
        if (SmsConsumoCredito::query()->where('sms_id', $smsId)->exists()) {
            
            throw new RuntimeException(
                'Os créditos desta SMS já foram consumidos.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | LOTES FIFO DA CONTA
        |--------------------------------------------------------------------------
        */
        $lotes = CarteiraLoteSms::query()
            ->where('conta_id', $contaId)
            ->where('estado', 'ACTIVO')
            ->where('quantidade_disponivel', '>', 0)
            ->orderBy('creditado_em')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $disponivel = (int) $lotes->sum('quantidade_disponivel');

        if ($disponivel < $segmentos) {
            throw new RuntimeException(
                'Saldo de lotes SMS insuficiente.'
            );
        }

        $restante = $segmentos;
        $valorTotal = '0.0000';
        $alocacoes = [];

        foreach ($lotes as $lote) {

            if ($restante <= 0) {
                break;
            }

            $saldoAnterior = (int) $lote->quantidade_disponivel;
            $consumir = min($saldoAnterior, $restante);
            $saldoPosterior = $saldoAnterior - $consumir;

            /*
            |--------------------------------------------------------------------------
            | RECEITA DESTE PEDAÇO
            |--------------------------------------------------------------------------
            */
            $valor = bcmul( (string) $consumir, (string) $lote->preco_venda_unitario, 4);

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
            | RASTREABILIDADE DA SMS
            |--------------------------------------------------------------------------
            */
            SmsConsumoCredito::create([
                'sms_id' => $smsId,
                'carteira_lote_sms_id' => $lote->id,
                'segmentos' => $consumir,
                'preco_venda_unitario' => $lote->preco_venda_unitario,
                'valor_total' => $valor,
            ]);

            $valorTotal = bcadd($valorTotal, $valor, 4);

            $alocacoes[] = [
                'lote_id' => $lote->id,
                'segmentos' => $consumir,
                'preco_venda_unitario' => (string) $lote->preco_venda_unitario,
                'valor_total' => $valor,
            ];

            $restante -= $consumir;
        }

        return [
            'sms_id' => $smsId,
            'segmentos' => $segmentos,
            'valor_total' => $valorTotal,
            'alocacoes' => $alocacoes,
        ];
    }
}