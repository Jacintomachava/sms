<?php

namespace App\Services\SMPP;

use App\Models\Sms;
use App\Models\SmsReserva;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SmsConfirmacaoService
{
    /**
     * Confirmar aceitação pelo SMSC.
     *
     * Só chamar após submit_sm_resp
     * com command_status = 0.
     */
    public function confirmarAceite(int $smsId, string $providerMessageId): void {

        DB::transaction(function () use (
            $smsId,
            $providerMessageId
        ) {
            $sms = Sms::query()
                ->lockForUpdate()
                ->findOrFail($smsId);

            // Idempotência: não cobrar novamente.
            if (in_array($sms->estado, [
                'ENVIADO',
                'ENTREGUE',
                'FALHADO',
                'EXPIRADO',
            ], true)) {
                if (
                    $sms->provider_message_id !==
                    $providerMessageId
                ) {
                    throw new RuntimeException(
                        'Conflito de identificação SMPP.'
                    );
                }

                return;
            }

            if (!in_array($sms->estado, [
                'PROCESSANDO',
                'INCERTO',
            ], true)) {
                throw new RuntimeException(
                    'Estado incompatível com aceitação.'
                );
            }

            $reservas = SmsReserva::query()
                ->where('sms_id', $smsId)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $cliente = $reservas->where('tipo', 'CLIENTE');

            $stock = $reservas->where('tipo', 'STOCK');

            $segmentos = (int) $sms->segmentos;

            if ($cliente->sum('quantidade') !== $segmentos || $stock->sum('quantidade') !== $segmentos) {

                throw new RuntimeException(
                    'Reservas inconsistentes.'
                );
            }

            if ($reservas->contains(

                fn ($reserva) => $reserva->estado !== 'RESERVADA'
            )) {
                throw new RuntimeException(
                    'Reserva em estado incompatível.'
                );
            }

            $carteira = DB::table('carteiras')
                ->where('conta_id', $sms->conta_id)
                ->lockForUpdate()
                ->first();

            if (!$carteira) {
                throw new RuntimeException(
                    'Carteira não encontrada.'
                );
            }

            /*
             * Consumir lotes comerciais.
             */
            foreach ($cliente as $reserva) {

                $lote = DB::table('carteira_lotes_sms')
                    ->where( 'id', $reserva->carteira_lote_sms_id)
                    ->lockForUpdate()
                    ->first();

                if (!$lote) {
                    throw new RuntimeException(
                        'Lote comercial inexistente.'
                    );
                }

                DB::table('carteira_lotes_sms')
                    ->where('id', $lote->id)
                    ->decrement('quantidade_disponivel', $reserva->quantidade);

                DB::table('carteira_lotes_sms')
                    ->where('id', $lote->id)
                    ->decrement('quantidade_reservada', $reserva->quantidade);

                DB::table('sms_consumos_creditos')
                    ->insert([
                        'sms_id' => $smsId,
                        'carteira_lote_sms_id' => $lote->id,
                        'segmentos' => $reserva->quantidade,
                        'preco_venda_unitario' => $lote->preco_venda_unitario,
                        'valor_total' => bcmul((string) $lote->preco_venda_unitario, (string) $reserva->quantidade,2),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
            }

            /*
             * Consumir stock Movitel.
             */
            foreach ($stock as $reserva) {
                $lote = DB::table('compras_stock_sms')
                    ->where('id', $reserva->compra_stock_sms_id)
                    ->lockForUpdate()
                    ->first();

                if (!$lote) {
                    throw new RuntimeException(
                        'Stock inexistente.'
                    );
                }

                DB::table('compras_stock_sms')
                    ->where('id', $lote->id)
                    ->decrement(
                        'quantidade_disponivel',
                        $reserva->quantidade
                    );

                DB::table('compras_stock_sms')
                    ->where('id', $lote->id)
                    ->decrement(
                        'quantidade_reservada',
                        $reserva->quantidade
                    );

                DB::table('sms_consumos_stock')
                    ->insert([
                        'sms_id' => $smsId,
                        'compra_stock_sms_id' => $lote->id,
                        'segmentos' => $reserva->quantidade,
                        'preco_compra_unitario' => $lote->preco_unitario,
                        'custo_total' => bcmul((string) $lote->preco_unitario, (string) $reserva->quantidade, 2),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
            }

            /*
             * Confirmar débito da carteira.
             */
            DB::table('carteiras')
                ->where('id', $carteira->id)
                ->update([
                    'saldo_sms' => $carteira->saldo_sms - $segmentos,
                    'saldo_reservado_sms' => $carteira->saldo_reservado_sms - $segmentos,
                    'updated_at' => now(),
                ]);

            /*
             * Finalizar reservas.
             */
            SmsReserva::query()
                ->where('sms_id', $smsId)
                ->update([
                    'estado' => 'CONSUMIDA',
                    'updated_at' => now(),
                ]);

            $sms->update([
                'estado' => 'ENVIADO',
                'provider' => 'MOVITEL',
                'provider_message_id' => $providerMessageId,
                'enviado_em' => now(),
            ]);
        }, 3);
    }
    
    public function libertarRejeitada(int $smsId): void
    {
        DB::transaction(function () use ($smsId) {

            $sms = Sms::query()
                ->lockForUpdate()
                ->findOrFail($smsId);

            if ($sms->estado === 'REJEITADO') {
                return;
            }

            if (!in_array($sms->estado, [
                'PROCESSANDO',
                'INCERTO',
            ], true)) {
                throw new RuntimeException(
                    'Estado incompatível com rejeição.'
                );
            }

            $reservas = SmsReserva::query()
                ->where('sms_id', $smsId)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($reservas->isEmpty()) {
                throw new RuntimeException(
                    'Nenhuma reserva encontrada.'
                );
            }

            foreach ($reservas as $reserva) {

                if ($reserva->estado !== 'RESERVADA') {
                    throw new RuntimeException(
                        'Reserva em estado incompatível.'
                    );
                }

                if ($reserva->tipo === 'CLIENTE') {
                    DB::table('carteira_lotes_sms')
                        ->where(
                            'id',
                            $reserva->carteira_lote_sms_id
                        )
                        ->decrement(
                            'quantidade_reservada',
                            $reserva->quantidade
                        );

                } elseif ($reserva->tipo === 'STOCK') {
                    DB::table('compras_stock_sms')
                        ->where(
                            'id',
                            $reserva->compra_stock_sms_id
                        )
                        ->decrement(
                            'quantidade_reservada',
                            $reserva->quantidade
                        );

                } else {
                    throw new RuntimeException(
                        'Tipo de reserva inválido.'
                    );
                }
            }

            $quantidadeCliente = $reservas
                ->where('tipo', 'CLIENTE')
                ->sum('quantidade');

            if ($quantidadeCliente > 0) {
                DB::table('carteiras')
                    ->where('conta_id', $sms->conta_id)
                    ->decrement(
                        'saldo_reservado_sms',
                        $quantidadeCliente
                    );
            }

            SmsReserva::query()
                ->where('sms_id', $smsId)
                ->update([
                    'estado' => 'LIBERTADA',
                    'updated_at' => now(),
                ]);

            $sms->update([
                'estado' => 'REJEITADO',
                'falhado_em' => now(),
            ]);
        }, 3);
    }

    public function marcarIncerta(int $smsId): void
    {
        DB::transaction(function () use ($smsId) {

            $sms = Sms::query()
                ->lockForUpdate()
                ->findOrFail($smsId);

            if ($sms->estado === 'INCERTO') {
                return;
            }

            if ($sms->estado !== 'PROCESSANDO') {
                throw new RuntimeException(
                    'Estado incompatível com incerteza.'
                );
            }

            $sms->update([
                'estado' => 'INCERTO',
            ]);

            /*
            * Não libertar reservas.
            * Não consumir créditos.
            * Não reenviar automaticamente.
            */
        }, 3);
    }

}
