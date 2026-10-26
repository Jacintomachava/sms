<?php

declare(strict_types=1);

namespace App\Services\SMPP;

use App\Models\Sms;
use App\Models\SmppTentativaEnvio;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Regista cada submit_sm_resp e só confirma financeiramente a SMS
 * quando TODOS os segmentos foram aceites.
 * Rejeição parcial nunca liberta reservas automaticamente.
 */
class SmppRespostaService
{
    public function __construct(private readonly SmsConfirmacaoService $confirmacao) {}

    public function processar(
        string $sessionId,
        int $sequenceNumber,
        int $commandStatus,
        ?string $providerMessageId
    ): void {
        if (trim($sessionId) === '' || $sequenceNumber < 1 ||
            $commandStatus < 0 || $commandStatus > 0xFFFFFFFF) {
            throw new RuntimeException('Identificação ou estado SMPP inválido.');
        }
        if ($commandStatus === 0 && ($providerMessageId === null || trim($providerMessageId) === '')) {
            throw new RuntimeException('Resposta SMPP aceite sem message_id.');
        }

        DB::transaction(function () use ($sessionId, $sequenceNumber, $commandStatus, $providerMessageId) {
            // Identificar antes de bloquear, preservando a ordem SMS -> tentativa -> segmentos.
            $referenciaSegmento = DB::table('smpp_segmentos_envio')
                ->where('session_id', $sessionId)
                ->where('sequence_number', $sequenceNumber)
                ->first();
            if (!$referenciaSegmento) {
                throw new RuntimeException('Segmento SMPP não encontrado para sessão/sequence.');
            }

            $referenciaTentativa = SmppTentativaEnvio::query()
                ->findOrFail($referenciaSegmento->smpp_tentativa_envio_id);
            $sms = Sms::query()->whereKey($referenciaTentativa->sms_id)
                ->lockForUpdate()->firstOrFail();
            $tentativa = SmppTentativaEnvio::query()->whereKey($referenciaTentativa->id)
                ->lockForUpdate()->firstOrFail();
            $segmentos = DB::table('smpp_segmentos_envio')
                ->where('smpp_tentativa_envio_id', $tentativa->id)
                ->orderBy('numero_segmento')->lockForUpdate()->get();
            $segmento = $segmentos->firstWhere('id', $referenciaSegmento->id);

            if (!$segmento || (int)$tentativa->sms_id !== (int)$sms->id ||
                $tentativa->session_id !== $sessionId ||
                $segmento->session_id !== $sessionId ||
                (int)$segmento->sequence_number !== $sequenceNumber ||
                $segmentos->count() !== (int)$sms->segmentos) {
                throw new RuntimeException('Associação ou quantidade de segmentos inconsistente.');
            }

            // Respostas duplicadas são idempotentes, mas respostas contraditórias são erro.
            if (in_array($segmento->estado, ['ACEITE', 'REJEITADO'], true)) {
                $igual = $segmento->estado === ($commandStatus === 0 ? 'ACEITE' : 'REJEITADO')
                    && (int)$segmento->command_status === $commandStatus
                    && ($commandStatus !== 0 || $segmento->provider_message_id === $providerMessageId);
                if (!$igual) {
                    throw new RuntimeException('Resposta duplicada contraditória para segmento SMPP.');
                }
                return;
            }
            if (!in_array($segmento->estado, ['SUBMETENDO', 'INCERTO'], true)) {
                throw new RuntimeException('Segmento não foi iniciado; resposta não pode ser aplicada.');
            }
            if (!in_array($tentativa->estado, ['SUBMETENDO', 'INCERTA'], true)) {
                throw new RuntimeException('Tentativa em estado incompatível com a resposta.');
            }

            DB::table('smpp_segmentos_envio')->where('id', $segmento->id)->update([
                'estado' => $commandStatus === 0 ? 'ACEITE' : 'REJEITADO',
                'command_status' => $commandStatus,
                'provider_message_id' => $commandStatus === 0 ? $providerMessageId : null,
                'respondido_em' => now(),
                'updated_at' => now(),
            ]);

            $segmentos = DB::table('smpp_segmentos_envio')
                ->where('smpp_tentativa_envio_id', $tentativa->id)
                ->orderBy('numero_segmento')->get();
            $aceites = $segmentos->where('estado', 'ACEITE');
            $rejeitados = $segmentos->where('estado', 'REJEITADO');
            $total = $segmentos->count();

            if ($aceites->count() === $total) {
                // Uma confirmação financeira para a SMS lógica inteira.
                $idPrincipal = (string)$aceites->sortByDesc('numero_segmento')
                    ->first()->provider_message_id;
                $this->confirmacao->confirmarAceite((int)$sms->id, $idPrincipal);
                $tentativa->update([
                    'estado' => 'ACEITE', 'command_status' => 0,
                    'provider_message_id' => $idPrincipal,
                    'respondida_em' => now(), 'erro' => null,
                ]);
                return;
            }

            if ($rejeitados->count() === $total) {
                // Todos foram explicitamente rejeitados; nenhum segmento aceite/incerto.
                $this->confirmacao->libertarRejeitada((int)$sms->id);
                $tentativa->update([
                    'estado' => 'REJEITADA', 'command_status' => $commandStatus,
                    'respondida_em' => now(),
                    'erro' => 'Todos os segmentos rejeitados pelo SMSC.',
                ]);
                return;
            }

            if ($rejeitados->isNotEmpty()) {

                if ($sms->estado === 'PROCESSANDO') {
                    $sms->update([
                        'estado' => 'INCERTO',
                    ]);
                }

                $tentativa->update([
                    'estado' => 'INCERTA',
                    'erro' => 'Resposta parcial/rejeição de segmento; requer reconciliação.',
                ]);

                DB::table('sms_reconciliacoes')
                    ->insertOrIgnore([
                        'sms_id' => $sms->id,
                        'smpp_tentativa_envio_id' => $tentativa->id,
                        'estado' => 'PENDENTE',
                        'tentativas' => 0,
                        'proxima_verificacao_em' => now(),
                        'motivo' => 'REJEICAO_PARCIAL',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
            }
            
            // Enquanto faltarem respostas, manter reservas intactas.
        }, 3);
    }
}
