<?php

namespace App\Services\SMPP;

use App\Models\Sms;
use App\Models\Conta;
use App\Models\CicloConsumoSms;
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
    public function confirmarAceite(int $smsId, string $providerMessageId): void
    {
        DB::transaction(function () use ($smsId, $providerMessageId) {

            /*
            |--------------------------------------------------------------------------
            | 1. BLOQUEAR A SMS
            |--------------------------------------------------------------------------
            */
            $sms = Sms::query()->lockForUpdate()->findOrFail($smsId);

            /*
            |--------------------------------------------------------------------------
            | 2. GARANTIR IDEMPOTÊNCIA
            |--------------------------------------------------------------------------
            */
            if (in_array($sms->estado, [
                'ENVIADO',
                'ENTREGUE',
                'FALHADO',
                'EXPIRADO',
            ], true)) {

                if ($sms->provider_message_id !== $providerMessageId) {
                    throw new RuntimeException(
                        'Conflito de identificação SMPP.'
                    );
                }

                // A SMS já foi confirmada. Não cobrar novamente.
                return;
            }

            /*
            |--------------------------------------------------------------------------
            | 3. VALIDAR O ESTADO DA SMS
            |--------------------------------------------------------------------------
            */
            if (!in_array($sms->estado, [
                'PROCESSANDO',
                'INCERTO',
            ], true)) {
                throw new RuntimeException(
                    'Estado incompatível com aceitação.'
                );
            }

            if (trim($providerMessageId) === '') {
                throw new RuntimeException(
                    'Identificador da Movitel não informado.'
                );
            }

            $segmentos = (int) $sms->segmentos;

            if ($segmentos < 1) {
                throw new RuntimeException(
                    'Número de segmentos inválido.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 4. VALIDAR RESPONSABILIDADE FINANCEIRA
            |--------------------------------------------------------------------------
            | Esta implementação confirma SMS custeadas pelo cliente.
            | SMS de cortesia/INFORDATA terão tratamento próprio.
            |--------------------------------------------------------------------------
            */
            if ($sms->custeado_por !== 'CLIENTE') {
                throw new RuntimeException(
                    'Confirmação financeira não implementada para SMS custeadas pela INFORDATA.'
                );
            }

            // Serializar movimentos financeiros desta conta.
            $conta = Conta::query()->whereKey($sms->conta_id)->lockForUpdate()->firstOrFail();
            $posPago = $conta->tipo_cobranca === 'POS_PAGO';
            if (!$posPago && $conta->tipo_cobranca !== 'PRE_PAGO') {
                throw new RuntimeException('Tipo de cobrança não suportado.');
            }

            // Apenas PRE_PAGO usa carteira; POS_PAGO usa ciclo mensal.
            $carteira = null;
            $saldoAnterior = null;
            $saldoReservado = null;
            if (!$posPago) {
                $carteira = DB::table('carteiras')
                    ->where('conta_id', $sms->conta_id)->whereNull('deleted_at')
                    ->lockForUpdate()->first();
                if (!$carteira) throw new RuntimeException('Carteira não encontrada.');
                $saldoAnterior = (int) $carteira->saldo_sms;
                $saldoReservado = (int) $carteira->saldo_reservado_sms;
                if ($saldoAnterior < $segmentos || $saldoReservado < $segmentos) {
                    throw new RuntimeException('Saldo ou reserva da carteira inconsistente.');
                }
            }

            /*
            |--------------------------------------------------------------------------
            | 7. BLOQUEAR AS RESERVAS DA SMS
            |--------------------------------------------------------------------------
            */
            $reservas = SmsReserva::query()
                ->where('sms_id', $smsId)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($reservas->isEmpty()) {
                throw new RuntimeException(
                    'Nenhuma reserva encontrada para esta SMS.'
                );
            }

            if ($reservas->contains(
                fn ($reserva) =>
                    $reserva->estado !== 'RESERVADA' ||
                    (int) $reserva->quantidade < 1
            )) {
                throw new RuntimeException(
                    'Reserva em estado incompatível ou com quantidade inválida.'
                );
            }

            $cliente = $reservas->where('tipo', 'CLIENTE');
            $stock = $reservas->where('tipo', 'STOCK');

            // PRE_PAGO = CLIENTE + STOCK; POS_PAGO = POS_PAGO + STOCK.
            $pos = $reservas->where('tipo', 'POS_PAGO');
            $financeiro = $posPago ? $pos : $cliente;
            if ((int) $financeiro->sum('quantidade') !== $segmentos ||
                (int) $stock->sum('quantidade') !== $segmentos ||
                $reservas->count() !== $financeiro->count() + $stock->count() ||
                ($posPago && ($pos->count() !== 1 || !$pos->first()->ciclo_consumo_sms_id))) {
                throw new RuntimeException('Reservas financeiras inconsistentes.');
            }

            /*
            |--------------------------------------------------------------------------
            | 9. CONSUMIR LOTES COMERCIAIS DO CLIENTE
            |--------------------------------------------------------------------------
            */
            foreach ($cliente->sortBy('carteira_lote_sms_id') as $reserva) {

                if (!$reserva->carteira_lote_sms_id || $reserva->compra_stock_sms_id) {
                    throw new RuntimeException(
                        'Associação inválida da reserva comercial.'
                    );
                }

                $lote = DB::table('carteira_lotes_sms')
                    ->where('id', $reserva->carteira_lote_sms_id)
                    ->where('conta_id', $sms->conta_id)
                    ->lockForUpdate()
                    ->first();

                if (!$lote) {
                    throw new RuntimeException(
                        'Lote comercial inexistente ou pertencente a outra conta.'
                    );
                }

                $quantidade = (int) $reserva->quantidade;

                if ((int) $lote->quantidade_disponivel < $quantidade || (int) $lote->quantidade_reservada < $quantidade) {
                    throw new RuntimeException(
                        'Saldo do lote comercial inconsistente.'
                    );
                }

                // Confirmar consumo do lote comercial.
                DB::table('carteira_lotes_sms')
                    ->where('id', $lote->id)
                    ->update([
                        'quantidade_disponivel' => (int) $lote->quantidade_disponivel - $quantidade,
                        'quantidade_reservada' => (int) $lote->quantidade_reservada - $quantidade,
                        'updated_at' => now(),
                    ]);

                // Guardar consumo comercial com preço histórico.
                DB::table('sms_consumos_creditos')->insert([
                    'sms_id' => $smsId,
                    'carteira_lote_sms_id' => $lote->id,
                    'segmentos' => $quantidade,
                    'preco_venda_unitario' => $lote->preco_venda_unitario,
                    'valor_total' => bcmul((string) $lote->preco_venda_unitario, (string) $quantidade, 2),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 10. CONSUMIR STOCK MOVITEL
            |--------------------------------------------------------------------------
            */
            foreach ($stock->sortBy('compra_stock_sms_id') as $reserva) {

                if (!$reserva->compra_stock_sms_id || $reserva->carteira_lote_sms_id) {
                    throw new RuntimeException(
                        'Associação inválida da reserva de stock.'
                    );
                }

                $lote = DB::table('compras_stock_sms')
                    ->where('id', $reserva->compra_stock_sms_id)
                    ->where('operadora', 'MOVITEL')
                    ->lockForUpdate()
                    ->first();

                if (!$lote) {
                    throw new RuntimeException(
                        'Stock Movitel inexistente.'
                    );
                }

                $quantidade = (int) $reserva->quantidade;

                if ((int) $lote->quantidade_disponivel < $quantidade || (int) $lote->quantidade_reservada < $quantidade) {
                    throw new RuntimeException(
                        'Stock Movitel inconsistente.'
                    );
                }

                // Confirmar consumo do stock.
                DB::table('compras_stock_sms')
                    ->where('id', $lote->id)
                    ->update([
                        'quantidade_disponivel' => (int) $lote->quantidade_disponivel - $quantidade,
                        'quantidade_reservada' => (int) $lote->quantidade_reservada - $quantidade,
                        'updated_at' => now(),
                    ]);

                // Guardar custo histórico do stock.
                DB::table('sms_consumos_stock')->insert([
                    'sms_id' => $smsId,
                    'compra_stock_sms_id' => $lote->id,
                    'segmentos' => $quantidade,
                    'preco_compra_unitario' => $lote->preco_unitario,
                    'custo_total' => bcmul((string) $lote->preco_unitario, (string) $quantidade, 2),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($posPago) {
                // Converter reserva do ciclo original em consumo, sem criar novo ciclo.
                $reservaPos = $pos->first();
                if ($reservaPos->carteira_lote_sms_id || $reservaPos->compra_stock_sms_id) {
                    throw new RuntimeException('Associação pós-paga inválida.');
                }
                $ciclo = CicloConsumoSms::query()
                    ->whereKey($reservaPos->ciclo_consumo_sms_id)
                    ->where('conta_id', $sms->conta_id)->lockForUpdate()->first();
                if (!$ciclo || $ciclo->estado !== 'ABERTO' ||
                    (int) $ciclo->segmentos_reservados < $segmentos) {
                    throw new RuntimeException('Ciclo pós-pago inexistente, fechado ou inconsistente.');
                }
                // Uma SMS = uma unidade; cobrança em segmentos.
                $valor = bcmul((string) $ciclo->preco_unitario, (string) $segmentos, 2);
                $ciclo->decrement('segmentos_reservados', $segmentos);
                $ciclo->increment('segmentos', $segmentos);
                $ciclo->increment('quantidade_sms', 1);
                $ciclo->increment('valor_estimado', $valor);
                
            } else {
            /*
            |--------------------------------------------------------------------------
            | 11. CONFIRMAR DÉBITO DA CARTEIRA
            |--------------------------------------------------------------------------
            */
            $saldoPosterior = $saldoAnterior - $segmentos;

            DB::table('carteiras')
                ->where('id', $carteira->id)
                ->update([
                    'saldo_sms' => $saldoPosterior,
                    'saldo_reservado_sms' =>$saldoReservado - $segmentos,
                    'updated_at' => now(),
                ]);

            /*
            |--------------------------------------------------------------------------
            | 12. REGISTAR MOVIMENTO FINANCEIRO
            |--------------------------------------------------------------------------
            */
            DB::table('carteira_movimentos')->insert([
                'carteira_id' => $carteira->id,
                'tipo' => 'DEBITO',
                'origem' => 'SMS',
                'quantidade_sms' => $segmentos,
                'saldo_anterior' => $saldoAnterior,
                'saldo_posterior' => $saldoPosterior,
                'referencia' => 'SMS-' . $sms->id,
                'chave_idempotencia' => 'SMS:' . $sms->id . ':DEBITO',
                'descricao' => 'Consumo de SMS aceite pela Movitel.',
                'user_id' => null,
                'metadata' => json_encode([
                    'sms_id' => $sms->id,
                    'provider' => 'MOVITEL',
                    'provider_message_id' => $providerMessageId,
                    'segmentos' => $segmentos,
                ], JSON_THROW_ON_ERROR),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            }

            /*
            |--------------------------------------------------------------------------
            | 13. FINALIZAR RESERVAS
            |--------------------------------------------------------------------------
            */
            SmsReserva::query()
                ->where('sms_id', $smsId)
                ->where('estado', 'RESERVADA')
                ->update([
                    'estado' => 'CONSUMIDA',
                    'updated_at' => now(),
                ]);

            /*
            |--------------------------------------------------------------------------
            | 14. ACTUALIZAR ESTADO DA SMS
            |--------------------------------------------------------------------------
            */
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

            /*
            |--------------------------------------------------------------------------
            | 1. BLOQUEAR A SMS
            |--------------------------------------------------------------------------
            */
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

            $segmentos = (int) $sms->segmentos;

            if ($segmentos < 1) {
                throw new RuntimeException(
                    'Número de segmentos inválido.'
                );
            }

            $conta = Conta::query()->whereKey($sms->conta_id)->lockForUpdate()->firstOrFail();
            $posPago = $conta->tipo_cobranca === 'POS_PAGO';
            if (!in_array($conta->tipo_cobranca, ['PRE_PAGO', 'POS_PAGO'], true)) {
                throw new RuntimeException('Tipo de cobrança não suportado.');
            }
            // Este fluxo de rejeição destina-se a SMS custeadas pelo cliente.
            if ($sms->custeado_por !== 'CLIENTE') {
                throw new RuntimeException('Financiamento não suportado.');
            }
            $carteira = null;
            if (!$posPago) {
                $carteira = DB::table('carteiras')
                    ->where('conta_id', $sms->conta_id)->whereNull('deleted_at')
                    ->lockForUpdate()->first();
                if (!$carteira) throw new RuntimeException('Carteira não encontrada.');
            }

            /*
            |--------------------------------------------------------------------------
            | 3. BLOQUEAR E VALIDAR AS RESERVAS
            |--------------------------------------------------------------------------
            */
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

            if ($reservas->contains(
                fn ($reserva) =>
                    $reserva->estado !== 'RESERVADA' ||
                    (int) $reserva->quantidade < 1
            )) {
                throw new RuntimeException(
                    'Reservas em estado incompatível.'
                );
            }

            $cliente = $reservas->where('tipo', 'CLIENTE');
            $stock = $reservas->where('tipo', 'STOCK');
            $pos = $reservas->where('tipo', 'POS_PAGO');
            $financeiro = $posPago ? $pos : $cliente;
            if ((int) $financeiro->sum('quantidade') !== $segmentos ||
                (int) $stock->sum('quantidade') !== $segmentos ||
                $reservas->count() !== $financeiro->count() + $stock->count() ||
                ($posPago && ($pos->count() !== 1 || !$pos->first()->ciclo_consumo_sms_id))) {
                throw new RuntimeException('Quantidades reservadas inconsistentes.');
            }

            /*
            |--------------------------------------------------------------------------
            | 4. LIBERTAR LOTES COMERCIAIS
            |--------------------------------------------------------------------------
            */
            foreach ($cliente->sortBy('carteira_lote_sms_id') as $reserva) {

                if (
                    !$reserva->carteira_lote_sms_id ||
                    $reserva->compra_stock_sms_id
                ) {
                    throw new RuntimeException(
                        'Associação comercial inválida.'
                    );
                }

                $lote = DB::table('carteira_lotes_sms')
                    ->where('id', $reserva->carteira_lote_sms_id)
                    ->where('conta_id', $sms->conta_id)
                    ->lockForUpdate()
                    ->first();

                if (!$lote) {
                    throw new RuntimeException(
                        'Lote comercial não encontrado.'
                    );
                }

                if (
                    (int) $lote->quantidade_reservada <
                    (int) $reserva->quantidade
                ) {
                    throw new RuntimeException(
                        'Reserva comercial inconsistente.'
                    );
                }

                DB::table('carteira_lotes_sms')
                    ->where('id', $lote->id)
                    ->decrement(
                        'quantidade_reservada',
                        (int) $reserva->quantidade
                    );
            }

            /*
            |--------------------------------------------------------------------------
            | 5. LIBERTAR STOCK MOVITEL
            |--------------------------------------------------------------------------
            */
            foreach ($stock->sortBy('compra_stock_sms_id') as $reserva) {

                if (
                    !$reserva->compra_stock_sms_id ||
                    $reserva->carteira_lote_sms_id
                ) {
                    throw new RuntimeException(
                        'Associação de stock inválida.'
                    );
                }

                $lote = DB::table('compras_stock_sms')
                    ->where('id', $reserva->compra_stock_sms_id)
                    ->where('operadora', 'MOVITEL')
                    ->lockForUpdate()
                    ->first();

                if (!$lote) {
                    throw new RuntimeException(
                        'Stock Movitel não encontrado.'
                    );
                }

                if (
                    (int) $lote->quantidade_reservada <
                    (int) $reserva->quantidade
                ) {
                    throw new RuntimeException(
                        'Reserva de stock inconsistente.'
                    );
                }

                DB::table('compras_stock_sms')
                    ->where('id', $lote->id)
                    ->decrement(
                        'quantidade_reservada',
                        (int) $reserva->quantidade
                    );
            }

            if ($posPago) {
                // Reverter apenas capacidade reservada, nunca consumo confirmado.
                $reservaPos = $pos->first();
                if ($reservaPos->carteira_lote_sms_id || $reservaPos->compra_stock_sms_id) {
                    throw new RuntimeException('Associação pós-paga inválida.');
                }
                $ciclo = CicloConsumoSms::query()
                    ->whereKey($reservaPos->ciclo_consumo_sms_id)
                    ->where('conta_id', $sms->conta_id)->lockForUpdate()->first();
                if (!$ciclo || (int) $ciclo->segmentos_reservados < $segmentos) {
                    throw new RuntimeException('Reserva mensal pós-paga inconsistente.');
                }
                $ciclo->decrement('segmentos_reservados', $segmentos);
            } else {
                if ((int) $carteira->saldo_reservado_sms < $segmentos) {
                    throw new RuntimeException('Reserva da carteira inconsistente.');
                }
                DB::table('carteiras')->where('id', $carteira->id)
                    ->decrement('saldo_reservado_sms', $segmentos);
            }

            /*
            |--------------------------------------------------------------------------
            | 7. FINALIZAR RESERVAS
            |--------------------------------------------------------------------------
            */
            SmsReserva::query()
                ->where('sms_id', $smsId)
                ->where('estado', 'RESERVADA')
                ->update([
                    'estado' => 'LIBERTADA',
                    'updated_at' => now(),
                ]);

            /*
            |--------------------------------------------------------------------------
            | 8. ACTUALIZAR SMS
            |--------------------------------------------------------------------------
            */
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
                ->whereKey($smsId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($sms->estado === 'INCERTO') {
                return;
            }

            if (in_array($sms->estado, [
                'ENVIADO',
                'ENTREGUE',
                'FALHADO',
                'REJEITADO',
                'EXPIRADO',
            ], true)) {
                return;
            }

            if ($sms->estado !== 'PROCESSANDO') {
                throw new RuntimeException(
                    'Estado incompatível com incerteza SMPP.'
                );
            }

            $sms->update([
                'estado' => 'INCERTO',
            ]);

        }, 3);
    }

}
