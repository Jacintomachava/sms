<?php

declare(strict_types=1);

namespace App\Services\SMPP;

use App\Models\Sms;
use App\Models\Conta;
use App\Models\SmsOutbox;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Validação e selecção. Não transmite PDUs nem movimenta créditos. */
class SmppEnvioService
{

    public function __construct(private readonly SmppUdhSegmentador $segmentador, private readonly SmppRetryService $retryService) {

    }

    public function proximaSms(array $ignorarSmsIds = []): ?Sms
    {
        $query = SmsOutbox::query()
            ->where('estado', 'PENDENTE')

            /*
            * A SMS deve estar pendente.
            */
            ->whereHas('sms', function ($q) {

                $q->where('estado', 'PENDENTE')
                    /*
                    * Respeitar o intervalo mínimo.
                    */
                    ->where(function ($sub) {
                        $sub->whereNull('proxima_tentativa_em')
                            ->orWhere(
                                'proxima_tentativa_em',
                                '<=',
                                now()
                            );
                    })

                    /*
                    * Não ultrapassar cinco tentativas.
                    */
                    ->where('tentativas_envio', '<', 5);
            })

            /*
            * Não pode existir tentativa anterior
            * com estado diferente de CANCELADA_SEM_ENVIO.
            *
            * Isto permite:
            * - Primeira tentativa;
            * - Retry de tentativas canceladas sem envio.
            */
            ->whereNotIn(
                'sms_id',
                DB::table('smpp_tentativas_envio')
                    ->select('sms_id')
                    ->where(
                        'estado',
                        '!=',
                        'CANCELADA_SEM_ENVIO'
                    )
            );

        if ($ignorarSmsIds !== []) {
            $query->whereNotIn('sms_id', $ignorarSmsIds);
        }

        return $query->orderBy('id')->first()?->sms;
    }

    public function validar(Sms $sms): void
    {
        if ($sms->estado !== 'PENDENTE') {
            throw new RuntimeException('A SMS não está pendente.');
        }
        if ($sms->custeado_por !== 'CLIENTE') {
            throw new RuntimeException('Financiamento da SMS ainda não suportado.');
        }
        $partes = $this->segmentador->preparar((string) $sms->mensagem, 1);
        if (count($partes) !== (int) $sms->segmentos) {
            throw new RuntimeException(sprintf(
                'Segmentos da SMS (%d) não correspondem aos segmentos UDH (%d). Corrigir antes do envio.',
                (int) $sms->segmentos, count($partes)
            ));
        }
        if (strtoupper((string) $sms->encoding) !== $partes[0]['encoding']) {
            throw new RuntimeException('Encoding da SMS não corresponde ao encoding UDH.');
        }
    }

    /** Verificação somente leitura; a reserva foi criada transaccionalmente. */
    public function verificarReservas(Sms $sms): void
    {
        $conta = Conta::query()->findOrFail($sms->conta_id);
        $reservas = DB::table('sms_reservas')->where('sms_id', $sms->id)->orderBy('id')->get();
        $segmentos = (int) $sms->segmentos;
        if ($segmentos < 1 || $reservas->isEmpty() || $reservas->contains(
            fn ($r) => $r->estado !== 'RESERVADA' || (int) $r->quantidade < 1
        )) throw new RuntimeException('Reservas financeiras inválidas.');
        $stock = $reservas->where('tipo', 'STOCK');
        if ((int) $stock->sum('quantidade') !== $segmentos) {
            throw new RuntimeException('Stock reservado insuficiente.');
        }
        if ($conta->tipo_cobranca === 'PRE_PAGO') {
            $financeiro = $reservas->where('tipo', 'CLIENTE');
        } elseif ($conta->tipo_cobranca === 'POS_PAGO') {
            $financeiro = $reservas->where('tipo', 'POS_PAGO');
            if ($financeiro->count() !== 1 || !$financeiro->first()->ciclo_consumo_sms_id ||
                !DB::table('ciclos_consumo_sms')->where('id', $financeiro->first()->ciclo_consumo_sms_id)
                    ->where('conta_id', $conta->id)->exists()) {
                throw new RuntimeException('Ciclo de reserva pós-paga inválido.');
            }
        } else throw new RuntimeException('Tipo de cobrança não suportado.');
        if ((int) $financeiro->sum('quantidade') !== $segmentos ||
            $reservas->count() !== $financeiro->count() + $stock->count()) {
            throw new RuntimeException('Reservas financeiras inconsistentes.');
        }
    }

    public function preparar(int $smsId): Sms
    {
        $sms = Sms::query()->findOrFail($smsId);

        /*
        * Valida mensagem, encoding e segmentos.
        */
        $this->validar($sms);

        /*
        * Confirma reservas existentes.
        * Não cria novas reservas.
        */
        $this->verificarReservas($sms);

        /*
        * Avaliação de elegibilidade.
        *
        * ATENÇÃO:
        * Esta avaliação não substitui a verificação
        * transaccional durante a criação da tentativa.
        */
        $autorizacao = $this->retryService->autorizar($smsId);

        if (!$autorizacao['permitido']) {
            throw new RuntimeException(
                'SMS não elegível para envio SMPP: '
                . $autorizacao['motivo']
            );
        }

        return $sms;
    }

    /** Compatibilidade com o worker antigo: exclusivamente SMS simples. */
    public function prepararTentativa(
        int $smsId, string $sessionId, int $sequenceNumber,
        string $host, int $porta, string $workerId,
        SmppTentativaService $tentativaService
    ): int {
        $sms = $this->preparar($smsId);
        if ((int) $sms->segmentos !== 1) {
            throw new RuntimeException('Worker antigo não suporta multipart; usar prepararSegmentos().');
        }
        return (int) $tentativaService->preparar(
            $smsId, $sessionId, $sequenceNumber, $host, $porta, $workerId
        )->id;
    }
}
