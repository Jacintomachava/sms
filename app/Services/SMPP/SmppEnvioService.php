<?php

declare(strict_types=1);

namespace App\Services\SMPP;

use App\Models\Sms;
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
            ->whereHas('sms', fn ($q) => $q->where('estado', 'PENDENTE'))
            ->whereNotIn('sms_id', DB::table('smpp_tentativas_envio')->select('sms_id'));
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

    public function verificarReservas(Sms $sms): void
    {
        $reservas = DB::table('sms_reservas')->where('sms_id', $sms->id)
            ->orderBy('id')->get();
        if ($reservas->isEmpty() || $reservas->contains(
            fn ($r) => $r->estado !== 'RESERVADA' || (int) $r->quantidade < 1 ||
                !in_array($r->tipo, ['CLIENTE', 'STOCK'], true)
        )) {
            throw new RuntimeException('Reservas financeiras inválidas.');
        }
        $segmentos = (int) $sms->segmentos;
        if ((int) $reservas->where('tipo', 'CLIENTE')->sum('quantidade') !== $segmentos ||
            (int) $reservas->where('tipo', 'STOCK')->sum('quantidade') !== $segmentos) {
            throw new RuntimeException('Reservas financeiras insuficientes.');
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
