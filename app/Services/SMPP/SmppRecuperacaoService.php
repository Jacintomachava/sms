<?php

declare(strict_types=1);

namespace App\Services\SMPP;

use App\Models\Sms;
use App\Models\SmppTentativaEnvio;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SmppRecuperacaoService
{
    public function recuperarSessao(string $sessionId): array
    {
        if (trim($sessionId) === '') {
            throw new RuntimeException(
                'Identificador da sessão SMPP inválido.'
            );
        }

        $ids = SmppTentativaEnvio::query()
            ->where('session_id', $sessionId)
            ->whereIn('estado', ['SUBMETENDO', 'INCERTA'])
            ->orderBy('id')
            ->pluck('id')
            ->all();

        return $this->recuperarIds($ids, $sessionId);
    }

    public function recuperarTentativasAbandonadas(
        ?string $sessaoActual = null
    ): array {

        $query = SmppTentativaEnvio::query()
            ->whereIn('estado', ['SUBMETENDO', 'INCERTA']);

        if ($sessaoActual !== null) {

            if (trim($sessaoActual) === '') {
                throw new RuntimeException('Sessão actual inválida.');
            }

            $query->where('session_id', '!=', $sessaoActual);
        }

        $ids = $query
            ->orderBy('id')
            ->pluck('id')
            ->all();

        return $this->recuperarIds($ids, $sessaoActual);
    }

    private function recuperarIds(
        array $ids,
        ?string $sessionId
    ): array {

        $recuperadas = 0;
        $ignoradas = 0;
        $erros = [];

        foreach ($ids as $id) {

            try {

                $resultado = $this->recuperarTentativa((int) $id);

                if ($resultado) {
                    $recuperadas++;
                } else {
                    $ignoradas++;
                }

            } catch (Throwable $e) {

                $erros[] = [
                    'tentativa_id' => (int) $id,
                    'erro' => $e->getMessage(),
                ];

                Log::error('Falha na recuperação SMPP', [
                    'tentativa_id' => (int) $id,
                    'session_id' => $sessionId,
                    'erro' => $e->getMessage(),
                ]);
            }
        }

        return compact(
            'recuperadas',
            'ignoradas',
            'erros'
        );
    }

    public function marcarTentativaIncerta(
        int $tentativaId
    ): bool {
        return $this->recuperarTentativa($tentativaId);
    }

    private function recuperarTentativa(
        int $tentativaId
    ): bool {

        return DB::transaction(function () use ($tentativaId): bool {

            $referencia = SmppTentativaEnvio::query()
                ->findOrFail($tentativaId);

            /*
             * Ordem dos locks:
             * SMS -> tentativa -> segmentos.
             */
            $sms = Sms::query()
                ->whereKey($referencia->sms_id)
                ->lockForUpdate()
                ->firstOrFail();

            $tentativa = SmppTentativaEnvio::query()
                ->whereKey($tentativaId)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $tentativa->sms_id !== (int) $sms->id) {
                throw new RuntimeException(
                    'Associação SMS/tentativa inconsistente.'
                );
            }

            /*
             * SMS já finalizada:
             * não alterar nem recuperar novamente.
             */
            if (in_array($sms->estado, [
                'FALHADO',
                'REJEITADO',
                'ENVIADO',
                'ENTREGUE',
                'EXPIRADO',
            ], true)) {

                return false;
            }

            if (!in_array($tentativa->estado, [
                'SUBMETENDO',
                'INCERTA',
            ], true)) {

                return false;
            }

            if (!in_array($sms->estado, [
                'PROCESSANDO',
                'INCERTO',
            ], true)) {

                throw new RuntimeException(
                    'Estado da SMS incompatível com recuperação.'
                );
            }

            /*
             * Bloquear segmentos.
             */
            $segmentos = DB::table('smpp_segmentos_envio')
                ->where(
                    'smpp_tentativa_envio_id',
                    $tentativaId
                )
                ->orderBy('numero_segmento')
                ->lockForUpdate()
                ->get();

            /*
             * Tentativa antiga sem segmentos.
             *
             * Não presumir rejeição.
             * Encaminhar para reconciliação.
             */
            if ($segmentos->isEmpty()) {

                if (
                    $tentativa->estado === 'INCERTA' &&
                    $sms->estado === 'INCERTO'
                ) {

                    $this->registarReconciliacao(
                        $sms->id,
                        $tentativaId,
                        'LEGADO_SEM_SEGMENTOS'
                    );

                    return false;
                }

                throw new RuntimeException(
                    'Tentativa sem segmentos; estado incompatível.'
                );
            }

            if (
                $segmentos->count() !==
                (int) $sms->segmentos
            ) {

                throw new RuntimeException(
                    'Quantidade de segmentos incompatível.'
                );
            }

            foreach ($segmentos as $segmento) {

                if (
                    $segmento->session_id !==
                    $tentativa->session_id
                ) {

                    throw new RuntimeException(
                        'Sessão SMPP inconsistente no segmento.'
                    );
                }

                if (!in_array($segmento->estado, [
                    'PREPARADO',
                    'SUBMETENDO',
                    'INCERTO',
                    'ACEITE',
                    'REJEITADO',
                ], true)) {

                    throw new RuntimeException(
                        'Estado de segmento desconhecido.'
                    );
                }
            }

            /*
             * Se já está INCERTA, apenas garantir
             * o registo para reconciliação.
             */
            if (
                $tentativa->estado === 'INCERTA' &&
                $sms->estado === 'INCERTO'
            ) {

                $this->registarReconciliacao(
                    $sms->id,
                    $tentativaId,
                    'RESULTADO_SMPP_INCERTO'
                );

                return false;
            }

            /*
             * Segmentos em submissão:
             * não sabemos se chegaram à Movitel.
             */
            $incertos = DB::table('smpp_segmentos_envio')
                ->where(
                    'smpp_tentativa_envio_id',
                    $tentativaId
                )
                ->where('estado', 'SUBMETENDO')
                ->update([
                    'estado' => 'INCERTO',
                    'updated_at' => now(),
                ]);

            if ($sms->estado === 'PROCESSANDO') {

                $sms->update([
                    'estado' => 'INCERTO',
                ]);
            }

            $tentativa->update([
                'estado' => 'INCERTA',
                'erro' =>
                    'Sessão SMPP encerrada sem confirmação de todos os segmentos.',
            ]);

            /*
             * Criar entrada de reconciliação.
             */
            $this->registarReconciliacao(
                $sms->id,
                $tentativaId,
                'SESSAO_INTERROMPIDA'
            );

            Log::warning(
                'Tentativa SMPP recuperada como INCERTA',
                [
                    'tentativa_id' => $tentativaId,
                    'sms_id' => $sms->id,
                    'segmentos_sem_resposta' => $incertos,
                ]
            );

            return true;

        }, 3);
    }

    private function registarReconciliacao(
        int $smsId,
        int $tentativaId,
        string $motivo
    ): void {

        /*
         * A tabela sms_reconciliacoes possui
         * índice UNIQUE em sms_id.
         *
         * Evitamos criar reconciliações duplicadas.
         */
        DB::table('sms_reconciliacoes')
            ->insertOrIgnore([
                'sms_id' => $smsId,
                'smpp_tentativa_envio_id' => $tentativaId,
                'estado' => 'PENDENTE',
                'tentativas' => 0,
                'proxima_verificacao_em' => now(),
                'motivo' => $motivo,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    }
}