<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class RegularizarSmsAntigas extends Command
{
    protected $signature = 'sms:regularizar-antigas {--confirmar}';

    protected $description = 'Regulariza cinco SMS antigas de teste, libertando reservas sem alterar consumos confirmados.';

    private const SMS_IDS = [2, 3, 4, 5, 6];

    public function handle(): int
    {
        $this->warn('Regularização administrativa de SMS antigas de teste.');
        $this->line('SMS seleccionadas: 2, 3, 4, 5, 6');

        if (!$this->option('confirmar')) {
            $this->info('Modo de simulação. Nenhuma alteração efectuada.');

            $sms = DB::table('sms')
                ->whereIn('id', self::SMS_IDS)
                ->select('id', 'estado', 'conta_id')
                ->get();

            $this->table(
                ['SMS', 'Estado', 'Conta'],
                $sms->map(fn ($item) => [
                    $item->id,
                    $item->estado,
                    $item->conta_id,
                ])->toArray()
            );

            return self::SUCCESS;
        }

        try {
            DB::transaction(function () {

                /*
                 * Bloquear SMS seleccionadas.
                 */
                $sms = DB::table('sms')
                    ->whereIn('id', self::SMS_IDS)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                if ($sms->count() !== count(self::SMS_IDS)) {
                    throw new RuntimeException(
                        'Uma ou mais SMS seleccionadas não existem.'
                    );
                }

                foreach ($sms as $item) {
                    if ($item->estado !== 'INCERTO') {
                        throw new RuntimeException(
                            "SMS {$item->id} não está INCERTO."
                        );
                    }
                }

                /*
                 * Não regularizar mensagens com consumos registados.
                 */
                foreach ([
                    'sms_consumos_creditos',
                    'sms_consumos_stock',
                ] as $tabela) {

                    $existe = DB::table($tabela)
                        ->whereIn('sms_id', self::SMS_IDS)
                        ->exists();

                    if ($existe) {
                        throw new RuntimeException(
                            "Existem consumos registados em {$tabela}."
                        );
                    }
                }

                /*
                 * Garantir que todas as tentativas são antigas,
                 * já INCERTAS e sem segmentos multipart.
                 */
                $tentativas = DB::table('smpp_tentativas_envio')
                    ->whereIn('sms_id', self::SMS_IDS)
                    ->lockForUpdate()
                    ->get();

                if ($tentativas->count() !== count(self::SMS_IDS)) {
                    throw new RuntimeException(
                        'Quantidade inesperada de tentativas SMPP.'
                    );
                }

                foreach ($tentativas as $tentativa) {

                    if ($tentativa->estado !== 'INCERTA') {
                        throw new RuntimeException(
                            "Tentativa {$tentativa->id} não está INCERTA."
                        );
                    }

                    $temSegmentos = DB::table('smpp_segmentos_envio')
                        ->where(
                            'smpp_tentativa_envio_id',
                            $tentativa->id
                        )
                        ->exists();

                    if ($temSegmentos) {
                        throw new RuntimeException(
                            "Tentativa {$tentativa->id} possui segmentos."
                        );
                    }
                }

                /*
                 * Bloquear reservas.
                 */
                $reservas = DB::table('sms_reservas')
                    ->whereIn('sms_id', self::SMS_IDS)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                if ($reservas->count() !== 10) {
                    throw new RuntimeException(
                        'Esperadas exactamente 10 reservas.'
                    );
                }

                foreach (self::SMS_IDS as $smsId) {

                    $grupo = $reservas->where('sms_id', $smsId);

                    if (
                        $grupo->count() !== 2 ||
                        $grupo->where('tipo', 'CLIENTE')->count() !== 1 ||
                        $grupo->where('tipo', 'STOCK')->count() !== 1
                    ) {
                        throw new RuntimeException(
                            "Reservas incompatíveis para SMS {$smsId}."
                        );
                    }
                }

                foreach ($reservas as $reserva) {

                    if (
                        $reserva->estado !== 'RESERVADA' ||
                        (int) $reserva->quantidade !== 1
                    ) {
                        throw new RuntimeException(
                            "Reserva {$reserva->id} inválida."
                        );
                    }
                }

                /*
                 * Bloquear carteira, lotes e stock.
                 */
                $contaId = (int) $sms->first()->conta_id;

                if ($sms->contains(
                    fn ($item) => (int) $item->conta_id !== $contaId
                )) {
                    throw new RuntimeException(
                        'As SMS pertencem a contas diferentes.'
                    );
                }

                $carteira = DB::table('carteiras')
                    ->where('conta_id', $contaId)
                    ->lockForUpdate()
                    ->first();

                if (!$carteira) {
                    throw new RuntimeException('Carteira não encontrada.');
                }

                $totalCliente = (int) $reservas
                    ->where('tipo', 'CLIENTE')
                    ->sum('quantidade');

                $totalStock = (int) $reservas
                    ->where('tipo', 'STOCK')
                    ->sum('quantidade');

                if ((int) $carteira->saldo_reservado_sms < $totalCliente) {
                    throw new RuntimeException(
                        'Saldo reservado da carteira insuficiente.'
                    );
                }

                $lotes = $reservas
                    ->where('tipo', 'CLIENTE')
                    ->groupBy('carteira_lote_sms_id');

                foreach ($lotes as $loteId => $grupo) {

                    $lote = DB::table('carteira_lotes_sms')
                        ->where('id', $loteId)
                        ->where('conta_id', $contaId)
                        ->lockForUpdate()
                        ->first();

                    $quantidade = (int) $grupo->sum('quantidade');

                    if (
                        !$lote ||
                        (int) $lote->quantidade_reservada < $quantidade
                    ) {
                        throw new RuntimeException(
                            "Reserva incompatível no lote {$loteId}."
                        );
                    }

                    DB::table('carteira_lotes_sms')
                        ->where('id', $loteId)
                        ->update([
                            'quantidade_disponivel' =>
                                $lote->quantidade_disponivel + $quantidade,

                            'quantidade_reservada' =>
                                $lote->quantidade_reservada - $quantidade,

                            'updated_at' => now(),
                        ]);
                }

                $stocks = $reservas
                    ->where('tipo', 'STOCK')
                    ->groupBy('compra_stock_sms_id');

                foreach ($stocks as $stockId => $grupo) {

                    $stock = DB::table('compras_stock_sms')
                        ->where('id', $stockId)
                        ->lockForUpdate()
                        ->first();

                    $quantidade = (int) $grupo->sum('quantidade');

                    if (
                        !$stock ||
                        (int) $stock->quantidade_reservada < $quantidade
                    ) {
                        throw new RuntimeException(
                            "Reserva incompatível no stock {$stockId}."
                        );
                    }

                    DB::table('compras_stock_sms')
                        ->where('id', $stockId)
                        ->update([
                            'quantidade_disponivel' =>
                                $stock->quantidade_disponivel + $quantidade,

                            'quantidade_reservada' =>
                                $stock->quantidade_reservada - $quantidade,

                            'updated_at' => now(),
                        ]);
                }

                /*
                 * Actualizar carteira.
                 */
                DB::table('carteiras')
                    ->where('id', $carteira->id)
                    ->update([
                        'saldo_sms' =>
                            $carteira->saldo_sms + $totalCliente,

                        'saldo_reservado_sms' =>
                            $carteira->saldo_reservado_sms - $totalCliente,

                        'updated_at' => now(),
                    ]);

                /*
                 * Libertar reservas.
                 */
                DB::table('sms_reservas')
                    ->whereIn('sms_id', self::SMS_IDS)
                    ->where('estado', 'RESERVADA')
                    ->update([
                        'estado' => 'LIBERTADA',
                        'updated_at' => now(),
                    ]);

                /*
                 * Regularização administrativa.
                 */
                DB::table('sms')
                    ->whereIn('id', self::SMS_IDS)
                    ->update([
                        'estado' => 'FALHADO',
                        'provider_descricao' =>
                            'Regularização administrativa de testes antigos; resultado na operadora não confirmado.',
                        'falhado_em' => now(),
                        'updated_at' => now(),
                    ]);

                /*
                 * Retirar da fila de envio.
                 */
                DB::table('sms_outbox')
                    ->whereIn('sms_id', self::SMS_IDS)
                    ->update([
                        'estado' => 'PROCESSADO',
                        'processado_em' => now(),
                        'updated_at' => now(),
                    ]);

            }, 3);

            $this->info(
                'Cinco SMS regularizadas e respectivas reservas libertadas.'
            );

            return self::SUCCESS;

        } catch (Throwable $e) {

            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}