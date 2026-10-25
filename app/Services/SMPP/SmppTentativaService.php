<?php

declare(strict_types=1);

namespace App\Services\SMPP;

use App\Models\SmppTentativaEnvio;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Prepara uma tentativa lógica e os seus segmentos SMPP.
 * Não envia PDUs, não processa respostas e não movimenta créditos.
 * IMPORTANTE: os restantes serviços precisam ser adaptados antes de activar multipart.
 */
class SmppTentativaService
{
    /** Compatibilidade com o worker de SMS simples existente. */
    public function preparar(
        int $smsId,
        string $sessionId,
        int $sequenceNumber,
        string $host,
        int $porta,
        string $workerId
    ): SmppTentativaEnvio {
        return $this->prepararSegmentos(
            $smsId, $sessionId, $host, $porta, $workerId,
            [['numero_segmento' => 1, 'sequence_number' => $sequenceNumber]],
            null
        );
    }

    /**
     * @param array<int,array{numero_segmento:int,sequence_number:int}> $segmentos
     * @param int|null $udhReferencia Inteiro entre 0 e 255; obrigatório em multipart.
     */
    public function prepararSegmentos(
        int $smsId,
        string $sessionId,
        string $host,
        int $porta,
        string $workerId,
        array $segmentos,
        ?int $udhReferencia
    ): SmppTentativaEnvio {
        if ($smsId < 1 || trim($sessionId) === '' || trim($host) === '' ||
            trim($workerId) === '' || $porta < 1 || $porta > 65535 || $segmentos === []) {
            throw new RuntimeException('Dados da tentativa SMPP inválidos.');
        }

        $total = count($segmentos);
        if ($total > 10 || ($total > 1 && ($udhReferencia === null || $udhReferencia < 0 || $udhReferencia > 255))) {
            throw new RuntimeException('Total de segmentos ou referência UDH inválidos.');
        }
        if ($total === 1 && $udhReferencia !== null) {
            throw new RuntimeException('SMS simples não deve ter referência UDH.');
        }

        $sequences = [];
        $numeros = [];
        foreach ($segmentos as $segmento) {
            $numero = $segmento['numero_segmento'] ?? null;
            $sequence = $segmento['sequence_number'] ?? null;
            if (!is_int($numero) || !is_int($sequence) || $numero < 1 || $numero > $total || $sequence < 1) {
                throw new RuntimeException('Metadados de segmento inválidos.');
            }
            if (isset($numeros[$numero]) || isset($sequences[$sequence])) {
                throw new RuntimeException('Número ou sequence_number duplicado.');
            }
            $numeros[$numero] = true;
            $sequences[$sequence] = true;
        }
        for ($i = 1; $i <= $total; $i++) {
            if (!isset($numeros[$i])) {
                throw new RuntimeException('Numeração de segmentos incompleta.');
            }
        }

        return DB::transaction(function () use (
            $smsId, $sessionId, $host, $porta, $workerId, $segmentos, $total, $udhReferencia
        ) {
            $sms = DB::table('sms')->where('id', $smsId)->lockForUpdate()->first();
            if (!$sms || $sms->estado !== 'PENDENTE') {
                throw new RuntimeException('SMS inexistente ou não pendente.');
            }
            if ((int) $sms->segmentos !== $total) {
                throw new RuntimeException('Segmentos transmitidos não coincidem com os créditos reservados.');
            }
            if ($sms->custeado_por !== 'CLIENTE') {
                throw new RuntimeException('Financiamento ainda não suportado neste fluxo.');
            }

            $reservas = DB::table('sms_reservas')->where('sms_id', $smsId)
                ->orderBy('id')->lockForUpdate()->get();
            if ($reservas->isEmpty() || $reservas->contains(
                fn ($r) => $r->estado !== 'RESERVADA' || (int) $r->quantidade < 1 ||
                    !in_array($r->tipo, ['CLIENTE', 'STOCK'], true)
            )) {
                throw new RuntimeException('Reservas da SMS inválidas.');
            }
            if ((int) $reservas->where('tipo', 'CLIENTE')->sum('quantidade') !== $total ||
                (int) $reservas->where('tipo', 'STOCK')->sum('quantidade') !== $total) {
                throw new RuntimeException('Reservas financeiras não correspondem aos segmentos.');
            }
            if (SmppTentativaEnvio::query()->where('sms_id', $smsId)->exists()) {
                throw new RuntimeException('A SMS já possui uma tentativa SMPP; reenvio automático proibido.');
            }

            $primeiro = collect($segmentos)->firstWhere('numero_segmento', 1);
            $tentativa = SmppTentativaEnvio::create([
                'sms_id' => $smsId,
                'host' => $host,
                'porta' => $porta,
                'worker_id' => $workerId,
                'session_id' => $sessionId,
                'sequence_number' => $primeiro['sequence_number'],
                'estado' => 'PREPARADA',
                'iniciada_em' => now(),
            ]);

            foreach ($segmentos as $segmento) {
                DB::table('smpp_segmentos_envio')->insert([
                    'smpp_tentativa_envio_id' => $tentativa->id,
                    'numero_segmento' => $segmento['numero_segmento'],
                    'total_segmentos' => $total,
                    'udh_referencia' => $udhReferencia,
                    'session_id' => $sessionId,
                    'sequence_number' => $segmento['sequence_number'],
                    'estado' => 'PREPARADO',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            return $tentativa;
        }, 3);
    }

    /**
     * Compatibilidade com a SMS simples: inicia a única submissão.
     * Para multipart, o worker deverá usar iniciarSegmento() antes de cada PDU.
     */
    public function iniciarSubmissao(int $tentativaId): void
    {
        $total = DB::table('smpp_segmentos_envio')
            ->where('smpp_tentativa_envio_id', $tentativaId)->count();
        if ($total !== 1) {
            throw new RuntimeException('Multipart deve iniciar cada segmento individualmente.');
        }
        $numero = (int) DB::table('smpp_segmentos_envio')
            ->where('smpp_tentativa_envio_id', $tentativaId)->value('numero_segmento');
        $this->iniciarSegmento($tentativaId, $numero);
    }

    /**
     * Persiste SUBMETENDO antes de transmitir a PDU.
     * Se o envio falhar depois desta transição, o resultado é INCERTO.
     */
    public function iniciarSegmento(int $tentativaId, int $numeroSegmento): void
    {
        DB::transaction(function () use ($tentativaId, $numeroSegmento) {
            $referencia = SmppTentativaEnvio::query()->findOrFail($tentativaId);
            $sms = DB::table('sms')->where('id', $referencia->sms_id)->lockForUpdate()->first();
            if (!$sms || !in_array($sms->estado, ['PENDENTE', 'PROCESSANDO'], true)) {
                throw new RuntimeException('Estado da SMS incompatível com submissão.');
            }
            $tentativa = SmppTentativaEnvio::query()->whereKey($tentativaId)
                ->lockForUpdate()->firstOrFail();
            if (!in_array($tentativa->estado, ['PREPARADA', 'SUBMETENDO'], true)) {
                throw new RuntimeException('Estado da tentativa incompatível com submissão.');
            }
            $segmento = DB::table('smpp_segmentos_envio')
                ->where('smpp_tentativa_envio_id', $tentativaId)
                ->where('numero_segmento', $numeroSegmento)
                ->lockForUpdate()->first();
            if (!$segmento || $segmento->estado !== 'PREPARADO') {
                throw new RuntimeException('Segmento inexistente ou já iniciado; reenvio proibido.');
            }
            $reservas = DB::table('sms_reservas')->where('sms_id', $sms->id)
                ->where('estado', 'RESERVADA')
                ->selectRaw('tipo, SUM(quantidade) AS total')->groupBy('tipo')
                ->pluck('total', 'tipo');
            $total = (int) $sms->segmentos;
            if ($total < 1 || $sms->custeado_por !== 'CLIENTE' ||
                (int) ($reservas['CLIENTE'] ?? 0) !== $total ||
                (int) ($reservas['STOCK'] ?? 0) !== $total) {
                throw new RuntimeException('Reservas financeiras incompatíveis.');
            }
            DB::table('smpp_segmentos_envio')->where('id', $segmento->id)->update([
                'estado' => 'SUBMETENDO',
                'submetido_em' => now(),
                'updated_at' => now(),
            ]);
            if ($tentativa->estado === 'PREPARADA') {
                $tentativa->update(['estado' => 'SUBMETENDO', 'iniciada_em' => now()]);
            }
            if ($sms->estado === 'PENDENTE') {
                DB::table('sms')->where('id', $sms->id)->update([
                    'estado' => 'PROCESSANDO', 'updated_at' => now(),
                ]);
            }
        }, 3);
    }
}
