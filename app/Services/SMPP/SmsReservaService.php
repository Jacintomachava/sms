<?php

namespace App\Services\SMPP;

use App\Models\Sms;
use App\Models\SmsReserva;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SmsReservaService
{
    public function reservar(int $smsId): void
    {
        DB::transaction(function () use ($smsId) {

            /*
             * Bloquear a SMS para evitar
             * processamento simultâneo.
             */
            $sms = Sms::query()->lockForUpdate()->findOrFail($smsId);

            if ($sms->estado !== 'PENDENTE') {
                throw new RuntimeException(
                    'A SMS não está pendente.'
                );
            }

            if ($sms->custeado_por !== 'CLIENTE') {
                throw new RuntimeException(
                    'Esta implementação suporta apenas SMS custeadas pelo cliente.'
                );
            }

            if (SmsReserva::where('sms_id', $sms->id)->exists()) {
                throw new RuntimeException(
                    'Esta SMS já possui reservas.'
                );
            }

            $quantidade = (int) $sms->segmentos;

            if ($quantidade < 1) {
                throw new RuntimeException(
                    'Número de segmentos inválido.'
                );
            }

            /*
             * 1. Reservar carteira.
             */
            $carteira = DB::table('carteiras')->where('conta_id', $sms->conta_id)->whereNull('deleted_at')->lockForUpdate()->first();

            if (!$carteira || $carteira->estado !== 'ACTIVA') {
                throw new RuntimeException(
                    'Carteira inexistente ou bloqueada.'
                );
            }

            $saldoLivre = (int) $carteira->saldo_sms - (int) $carteira->saldo_reservado_sms;

            if ($saldoLivre < $quantidade) {
                throw new RuntimeException(
                    'Saldo SMS insuficiente.'
                );
            }

            /*
             * 2. Reservar lotes comerciais FIFO.
             */
            $restante = $quantidade;

            $lotes = DB::table('carteira_lotes_sms')
                ->where('conta_id', $sms->conta_id)
                ->where('estado', 'ACTIVO')
                ->orderBy('creditado_em')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($lotes as $lote) {

                if ($restante === 0) {
                    break;
                }

                $livre = (int) $lote->quantidade_disponivel - (int) $lote->quantidade_reservada;

                $usar = min($restante, max(0, $livre));

                if ($usar === 0) {
                    continue;
                }

                DB::table('carteira_lotes_sms')->where('id', $lote->id)->increment('quantidade_reservada', $usar);

                SmsReserva::create([
                    'sms_id' => $sms->id,
                    'carteira_lote_sms_id' => $lote->id,
                    'tipo' => 'CLIENTE',
                    'quantidade' => $usar,
                    'estado' => 'RESERVADA',
                ]);

                $restante -= $usar;
            }

            if ($restante > 0) {
                throw new RuntimeException(
                    'Lotes comerciais insuficientes.'
                );
            }

            DB::table('carteiras')
                ->where('id', $carteira->id)
                ->increment('saldo_reservado_sms', $quantidade);

            /*
             * 3. Reservar stock da INFORDATA.
             */
            $restante = $quantidade;

            $stocks = DB::table('compras_stock_sms')
                ->where('operadora', 'MOVITEL')
                ->where('estado', 'ACTIVO')
                ->orderBy('data_compra')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($stocks as $stock) {

                if ($restante === 0) {
                    break;
                }

                $livre = (int) $stock->quantidade_disponivel - (int) $stock->quantidade_reservada;

                $usar = min($restante, max(0, $livre));

                if ($usar === 0) {
                    continue;
                }

                DB::table('compras_stock_sms')
                    ->where('id', $stock->id)
                    ->increment('quantidade_reservada', $usar);

                SmsReserva::create([
                    'sms_id' => $sms->id,
                    'compra_stock_sms_id' => $stock->id,
                    'tipo' => 'STOCK',
                    'quantidade' => $usar,
                    'estado' => 'RESERVADA',
                ]);

                $restante -= $usar;
            }

            if ($restante > 0) {
                throw new RuntimeException(
                    'Stock Movitel insuficiente.'
                );
            }

            /*
             * A transacção só é confirmada
             * se carteira, lotes e stock
             * tiverem sido reservados.
             */
        }, 3);
    }
}
