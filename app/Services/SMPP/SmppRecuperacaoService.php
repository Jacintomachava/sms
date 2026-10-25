<?php

declare(strict_types=1);

namespace App\Services\SMPP;

use App\Models\Sms;
use App\Models\SmppTentativaEnvio;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Recuperação conservadora: não retransmite PDUs nem movimenta reservas.
 * Exige que o worker detenha o lock exclusivo antes de recuperar abandonadas.
 */
class SmppRecuperacaoService
{
    public function recuperarSessao(string $sessionId): array
    {
        if (trim($sessionId) === '') {
            throw new RuntimeException('Identificador da sessão SMPP inválido.');
        }

        return $this->recuperarIds(
            SmppTentativaEnvio::query()
                ->where('session_id', $sessionId)
                ->where('estado', 'SUBMETENDO')
                ->orderBy('id')->pluck('id')->all(),
            $sessionId
        );
    }

    /**
     * Recupera tentativas abandonadas. Chamar somente com lock exclusivo do worker.
     */
    public function recuperarTentativasAbandonadas(?string $sessaoActual = null): array
    {
        $query = SmppTentativaEnvio::query()->where('estado', 'SUBMETENDO');
        if ($sessaoActual !== null) {
            if (trim($sessaoActual) === '') {
                throw new RuntimeException('Sessão actual inválida.');
            }
            $query->where('session_id', '!=', $sessaoActual);
        }

        return $this->recuperarIds($query->orderBy('id')->pluck('id')->all(), $sessaoActual);
    }

    private function recuperarIds(array $ids, ?string $sessionId): array
    {
        $recuperadas = 0;
        $ignoradas = 0;
        $erros = [];

        foreach ($ids as $id) {
            try {
                if ($this->marcarTentativaIncerta((int) $id)) {
                    $recuperadas++;
                } else {
                    $ignoradas++;
                }
            } catch (Throwable $e) {
                $erros[] = ['tentativa_id' => (int) $id, 'erro' => $e->getMessage()];
                Log::error('Falha na recuperação SMPP multipart', [
                    'tentativa_id' => (int) $id,
                    'session_id' => $sessionId,
                    'erro' => $e->getMessage(),
                ]);
            }
        }

        return compact('recuperadas', 'ignoradas', 'erros');
    }

    /**
     * Ordem dos locks: SMS -> tentativa -> segmentos.
     * Apenas segmentos SUBMETENDO tornam-se INCERTO; ACEITE/REJEITADO
     * permanecem intactos. Segmentos PREPARADO não são considerados enviados.
     */
    public function marcarTentativaIncerta(int $tentativaId): bool
    {
        return DB::transaction(function () use ($tentativaId): bool {
            $referencia = SmppTentativaEnvio::query()->findOrFail($tentativaId);

            $sms = Sms::query()->whereKey($referencia->sms_id)
                ->lockForUpdate()->firstOrFail();
            $tentativa = SmppTentativaEnvio::query()->whereKey($tentativaId)
                ->lockForUpdate()->firstOrFail();

            if ((int) $tentativa->sms_id !== (int) $sms->id) {
                throw new RuntimeException('Associação SMS/tentativa inconsistente.');
            }

            if (in_array($tentativa->estado, ['ACEITE', 'REJEITADA', 'INCERTA'], true)) {
                return false;
            }
            if ($tentativa->estado !== 'SUBMETENDO') {
                return false;
            }
            if (!in_array($sms->estado, ['PROCESSANDO', 'INCERTO'], true)) {
                throw new RuntimeException('Estado da SMS incompatível com recuperação.');
            }

            $segmentos = DB::table('smpp_segmentos_envio')
                ->where('smpp_tentativa_envio_id', $tentativaId)
                ->orderBy('numero_segmento')->lockForUpdate()->get();

            if ($segmentos->count() !== (int) $sms->segmentos || $segmentos->isEmpty()) {
                throw new RuntimeException('Segmentos ausentes ou quantidade incompatível.');
            }
            foreach ($segmentos as $segmento) {
                if ($segmento->session_id !== $tentativa->session_id) {
                    throw new RuntimeException('Sessão SMPP inconsistente no segmento.');
                }
                if (!in_array($segmento->estado, [
                    'PREPARADO', 'SUBMETENDO', 'INCERTO', 'ACEITE', 'REJEITADO'
                ], true)) {
                    throw new RuntimeException('Estado de segmento desconhecido.');
                }
            }

            $incertos = DB::table('smpp_segmentos_envio')
                ->where('smpp_tentativa_envio_id', $tentativaId)
                ->where('estado', 'SUBMETENDO')
                ->update(['estado' => 'INCERTO', 'updated_at' => now()]);

            if ($sms->estado === 'PROCESSANDO') {
                $sms->update(['estado' => 'INCERTO']);
            }

            $tentativa->update([
                'estado' => 'INCERTA',
                'erro' => 'Sessão SMPP encerrada sem confirmação de todos os segmentos; reconciliação necessária.',
            ]);

            Log::warning('Tentativa SMPP marcada INCERTA', [
                'tentativa_id' => $tentativaId,
                'sms_id' => $sms->id,
                'segmentos_sem_resposta' => $incertos,
            ]);

            return true;
        }, 3);
    }
}
