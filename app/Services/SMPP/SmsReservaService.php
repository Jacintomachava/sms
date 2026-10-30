<?php

namespace App\Services\SMPP;

use App\Models\Conta;
use App\Models\CicloConsumoSms;
use App\Models\Sms;
use App\Models\SmsReserva;
use App\Services\TarifaSmsService;
use App\Services\LimiteInternoSmsService;
use App\Services\SuspensaoFinanceiraSmsService;
use App\Services\LimiteSmsService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SmsReservaService
{
    public function __construct(
        private TarifaSmsService $tarifas,
        private LimiteInternoSmsService $limiteInterno,
        private LimiteSmsService $limiteCliente,
        private SuspensaoFinanceiraSmsService $suspensaoFinanceira,
    ) {}

    public function reservar(int $smsId): void
    {
        /*
        |--------------------------------------------------------------------------
        | VERIFICAR SUSPENSÃO FINANCEIRA
        |--------------------------------------------------------------------------
        | Esta verificação é feita numa transacção própria.
        | Assim, se a conta for suspensa, a alteração fica gravada.
        */
        $smsContaId = Sms::query()->whereKey($smsId)->value('conta_id');

        if (!$smsContaId) {
            throw new RuntimeException('SMS não encontrado.');
        }

        $this->suspensaoFinanceira->verificar((int) $smsContaId);

        DB::transaction(function () use ($smsId) {
            // Ordem de bloqueios: SMS -> conta -> recursos financeiros -> stock.
            $sms = Sms::query()->lockForUpdate()->findOrFail($smsId);
            if ($sms->estado !== 'PENDENTE' || $sms->custeado_por !== 'CLIENTE') {
                throw new RuntimeException('SMS não elegível para reserva.');
            }
            if (SmsReserva::where('sms_id', $sms->id)->exists()) {
                throw new RuntimeException('SMS já possui reservas.');
            }
            $quantidade = (int) $sms->segmentos;
            if ($quantidade < 1) {
                throw new RuntimeException('Número de segmentos inválido.');
            }

           // Serializar reservas da mesma conta, inclusive na criação do ciclo.
            $conta = Conta::query()
                ->whereKey($sms->conta_id)
                ->lockForUpdate()
                ->firstOrFail();

            /*
            |--------------------------------------------------------------------------
            | VALIDAR ESTADO DA CONTA
            |--------------------------------------------------------------------------
            */
            if ($conta->estado !== 'ACTIVA') {

                if (
                    $conta->estado === 'SUSPENSA' &&
                    $conta->motivo_suspensao === 'FACTURAS_EM_ATRASO'
                ) {
                    throw new RuntimeException(
                        'Conta suspensa por facturas em atraso. Regularize os pagamentos.'
                    );
                }

                throw new RuntimeException(
                    'Conta suspensa ou bloqueada. Não é permitido enviar SMS.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | VALIDAR COBRANÇA
            |--------------------------------------------------------------------------
            */
            if ($conta->tipo_cobranca === 'PRE_PAGO') {
                $this->reservarPrePago($sms, $quantidade);
            } elseif ($conta->tipo_cobranca === 'POS_PAGO') {
                $this->reservarPosPago($sms, $conta, $quantidade);
            } else {
                throw new RuntimeException('Tipo de cobrança não suportado.');
            }

            // O stock da Movitel é sempre reservado, mesmo no pós-pago.
            $this->reservarStock($sms, $quantidade);
            
        }, 3);
    }

    private function reservarPrePago(Sms $sms, int $quantidade): void
    {
        $carteira = DB::table('carteiras')
            ->where('conta_id', $sms->conta_id)->whereNull('deleted_at')
            ->lockForUpdate()->first();
        if (!$carteira || $carteira->estado !== 'ACTIVA') {
            throw new RuntimeException('Carteira inexistente ou bloqueada.');
        }
        if ((int) $carteira->saldo_sms - (int) $carteira->saldo_reservado_sms < $quantidade) {
            throw new RuntimeException('Saldo SMS insuficiente.');
        }

        // FIFO comercial existente: manter o preço histórico de cada lote.
        $restante = $quantidade;
        $lotes = DB::table('carteira_lotes_sms')
            ->where('conta_id', $sms->conta_id)->where('estado', 'ACTIVO')
            ->orderBy('creditado_em')->orderBy('id')->lockForUpdate()->get();
        foreach ($lotes as $lote) {
            if ($restante === 0) break;
            $livre = (int) $lote->quantidade_disponivel - (int) $lote->quantidade_reservada;
            $usar = min($restante, max(0, $livre));
            if ($usar === 0) continue;
            DB::table('carteira_lotes_sms')->where('id', $lote->id)->increment('quantidade_reservada', $usar);
            SmsReserva::create([
                'sms_id' => $sms->id, 'carteira_lote_sms_id' => $lote->id,
                'tipo' => 'CLIENTE', 'quantidade' => $usar, 'estado' => 'RESERVADA',
            ]);
            $restante -= $usar;
        }
        if ($restante > 0) throw new RuntimeException('Lotes comerciais insuficientes.');
        DB::table('carteiras')->where('id', $carteira->id)->increment('saldo_reservado_sms', $quantidade);
    }

    private function reservarPosPago(Sms $sms, Conta $conta, int $quantidade): void
    {
        $tarifa = $this->tarifas->obterTarifaPosPago($conta);
        $inicio = now()->startOfMonth()->toDateString();
        $fim = now()->endOfMonth()->toDateString();

        // A conta já está bloqueada; impede criação concorrente do mesmo ciclo.
        $ciclo = CicloConsumoSms::query()
            ->where('conta_id', $conta->id)
            ->where('periodo_inicio', $inicio)->where('periodo_fim', $fim)
            ->lockForUpdate()->first();
        if (!$ciclo) {
            $ciclo = CicloConsumoSms::create([
                'conta_id' => $conta->id,
                'periodo_inicio' => $inicio, 'periodo_fim' => $fim,
                'quantidade_sms' => 0, 'segmentos' => 0,
                'segmentos_reservados' => 0,
                'tarifa_sms_id' => $tarifa->id,
                'preco_unitario' => $tarifa->preco_sms,
                'valor_estimado' => 0, 'moeda' => 'MZN', 'estado' => 'ABERTO',
            ]);
        }
        if ($ciclo->estado !== 'ABERTO') {
            throw new RuntimeException('Ciclo pós-pago não está aberto.');
        }

        // As validações lêem consumo confirmado + reservado.
        // Executar sempre antes de incrementar a reserva.
        $this->limiteInterno->validarEnvio($conta, $quantidade);
        $this->limiteCliente->validarEnvio($conta, $quantidade);

        $ciclo->increment('segmentos_reservados', $quantidade);
        SmsReserva::create([
            'sms_id' => $sms->id,
            'ciclo_consumo_sms_id' => $ciclo->id,
            'tipo' => 'POS_PAGO', 'quantidade' => $quantidade,
            'estado' => 'RESERVADA',
        ]);
    }

    private function reservarStock(Sms $sms, int $quantidade): void
    {
        $restante = $quantidade;
        $stocks = DB::table('compras_stock_sms')
            ->where('operadora', 'MOVITEL')->where('estado', 'ACTIVO')
            ->orderBy('data_compra')->orderBy('id')->lockForUpdate()->get();
        foreach ($stocks as $stock) {
            if ($restante === 0) break;
            $livre = (int) $stock->quantidade_disponivel - (int) $stock->quantidade_reservada;
            $usar = min($restante, max(0, $livre));
            if ($usar === 0) continue;
            DB::table('compras_stock_sms')->where('id', $stock->id)->increment('quantidade_reservada', $usar);
            SmsReserva::create([
                'sms_id' => $sms->id, 'compra_stock_sms_id' => $stock->id,
                'tipo' => 'STOCK', 'quantidade' => $usar, 'estado' => 'RESERVADA',
            ]);
            $restante -= $usar;
        }
        if ($restante > 0) throw new RuntimeException('Stock Movitel insuficiente.');
    }
}
