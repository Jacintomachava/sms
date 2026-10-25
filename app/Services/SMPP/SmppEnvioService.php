<?php
namespace App\Services\SMPP;

use App\Models\Sms;
use App\Models\SmsOutbox;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SmppEnvioService
{
    public function proximaSms(array $ignorarSmsIds = []): ?Sms
    {
        $query = SmsOutbox::query()
            ->where('estado', 'PENDENTE')
            ->whereHas('sms', fn ($query) => $query->where('estado', 'PENDENTE'))
            ->whereNotIn('sms_id', DB::table('smpp_tentativas_envio')->select('sms_id'));
        if ($ignorarSmsIds !== []) {
            $query->whereNotIn('sms_id', $ignorarSmsIds);
        }
        return $query->orderBy('id')->first()?->sms;
    }

    public function validar(Sms $sms): void
    {
        if ($sms->estado !== 'PENDENTE') throw new RuntimeException('A SMS não está pendente.');
        if ((int) $sms->segmentos !== 1) throw new RuntimeException('Esta versão suporta apenas SMS de um segmento.');
        $mensagem = (string) $sms->mensagem;
        if ($mensagem === '' || strlen($mensagem) > 160 || preg_match('/[^\x20-\x7E]/', $mensagem) || strpbrk($mensagem, '@$[]\\^{}|~`') !== false) {
            throw new RuntimeException('Mensagem incompatível com o SMPP actual.');
        }
        if ($sms->custeado_por !== 'CLIENTE') throw new RuntimeException('Financiamento da SMS ainda não suportado.');
    }

    public function verificarReservas(Sms $sms): void
    {
        $reservas = DB::table('sms_reservas')->where('sms_id', $sms->id)->where('estado', 'RESERVADA')
            ->selectRaw('tipo, SUM(quantidade) AS total')->groupBy('tipo')->pluck('total', 'tipo');
        $segmentos = (int) $sms->segmentos;
        if ((int) ($reservas['CLIENTE'] ?? 0) !== $segmentos || (int) ($reservas['STOCK'] ?? 0) !== $segmentos) {
            throw new RuntimeException('Reservas financeiras insuficientes.');
        }
    }

    public function preparar(int $smsId): Sms
    {
        $sms = Sms::query()->findOrFail($smsId);
        $this->validar($sms);
        $this->verificarReservas($sms);
        if (DB::table('smpp_tentativas_envio')->where('sms_id', $smsId)->exists()) {
            throw new RuntimeException('Esta SMS já possui uma tentativa SMPP.');
        }
        return $sms;
    }

    public function prepararTentativa(int $smsId, string $sessionId, int $sequenceNumber, string $host, int $porta, string $workerId, SmppTentativaService $tentativaService): int
    {
        $sms = $this->preparar($smsId);
        $tentativa = $tentativaService->preparar(
            smsId: $sms->id, sessionId: $sessionId, sequenceNumber: $sequenceNumber,
            host: $host, porta: $porta, workerId: $workerId
        );
        return (int) $tentativa->id;
    }
}
