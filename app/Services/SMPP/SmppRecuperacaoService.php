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

    /**
     * Identifica tentativas PREPARADA potencialmente abandonadas.
     *
     * Apenas diagnóstico:
     * - Não altera estados.
     * - Não cria novas tentativas.
     * - Não envia SMS.
     * - Não movimenta reservas.
     *
     * Deve ser executado apenas num contexto em que
     * a sessão SMPP anterior esteja comprovadamente encerrada.
     */
    public function diagnosticarPreparadas(int $limite = 100): array
    {
        if ($limite < 1 || $limite > 1000) {
            throw new RuntimeException('Limite inválido.');
        }

        $tentativas = SmppTentativaEnvio::query()
            ->where('estado', 'PREPARADA')
            ->orderBy('id')
            ->limit($limite)
            ->get();

        $resultado = [
            'analisadas' => 0,
            'candidatas' => [],
            'bloqueadas' => [],
        ];

        foreach ($tentativas as $tentativa) {

            $resultado['analisadas']++;

            $sms = DB::table('sms')
                ->where('id', $tentativa->sms_id)
                ->first();

            if (!$sms || $sms->estado !== 'PENDENTE') {

                $resultado['bloqueadas'][] = [
                    'tentativa_id' => $tentativa->id,
                    'sms_id' => $tentativa->sms_id,
                    'motivo' => 'SMS_NAO_PENDENTE',
                ];

                continue;
            }

            $segmentos = DB::table('smpp_segmentos_envio')
                ->where(
                    'smpp_tentativa_envio_id',
                    $tentativa->id
                )
                ->get();

            if (
                $segmentos->count() !== (int) $sms->segmentos
            ) {

                $resultado['bloqueadas'][] = [
                    'tentativa_id' => $tentativa->id,
                    'sms_id' => $sms->id,
                    'motivo' => 'SEGMENTOS_INCOMPATÍVEIS',
                ];

                continue;
            }

            $todosPreparados = $segmentos->every(
                fn ($segmento) =>
                    $segmento->estado === 'PREPARADO'
                    && $segmento->submetido_em === null
                    && $segmento->respondido_em === null
                    && $segmento->provider_message_id === null
            );

            if (!$todosPreparados) {

                $resultado['bloqueadas'][] = [
                    'tentativa_id' => $tentativa->id,
                    'sms_id' => $sms->id,
                    'motivo' => 'SEGMENTO_INICIADO',
                ];

                continue;
            }

            $reservas = DB::table('sms_reservas')
                ->where('sms_id', $sms->id)
                ->get();

            $reservasValidas =
                $reservas->isNotEmpty()
                && $reservas->every(
                    fn ($reserva) =>
                        $reserva->estado === 'RESERVADA'
                        && (int) $reserva->quantidade > 0
                )
                && (int) $reservas
                    ->where('tipo', 'CLIENTE')
                    ->sum('quantidade') === (int) $sms->segmentos
                && (int) $reservas
                    ->where('tipo', 'STOCK')
                    ->sum('quantidade') === (int) $sms->segmentos;

            if (!$reservasValidas) {

                $resultado['bloqueadas'][] = [
                    'tentativa_id' => $tentativa->id,
                    'sms_id' => $sms->id,
                    'motivo' => 'RESERVAS_INVALIDAS',
                ];

                continue;
            }

            $resultado['candidatas'][] = [
                'tentativa_id' => $tentativa->id,
                'sms_id' => $sms->id,
                'segmentos' => $sms->segmentos,
                'criada_em' => $tentativa->created_at,
                'motivo' => 'PREPARADA_SEM_SUBMISSAO_REGISTADA',
            ];
        }

        return $resultado;
    }


    /**
     * Cancela uma tentativa preparada que nunca iniciou
     * a transmissão de qualquer segmento.
     *
     * ATENÇÃO:
     * O chamador deve garantir que o worker anterior
     * terminou e não pode continuar a transmitir.
     *
     * Não altera reservas nem cria nova tentativa.
     */
    public function cancelarPreparadaSemEnvio(int $tentativaId): bool {

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

            if ($tentativa->estado === 'CANCELADA_SEM_ENVIO') {
                return false;
            }

            if (
                $sms->estado !== 'PENDENTE' ||
                $tentativa->estado !== 'PREPARADA'
            ) {
                throw new RuntimeException(
                    'Tentativa não elegível para cancelamento sem envio.'
                );
            }

            $segmentos = DB::table('smpp_segmentos_envio')
                ->where('smpp_tentativa_envio_id', $tentativaId)
                ->orderBy('numero_segmento')
                ->lockForUpdate()
                ->get();

            if ($segmentos->count() !== (int) $sms->segmentos) {
                throw new RuntimeException(
                    'Quantidade de segmentos incompatível.'
                );
            }

            foreach ($segmentos as $segmento) {

                if (
                    $segmento->estado !== 'PREPARADO' ||
                    $segmento->submetido_em !== null ||
                    $segmento->respondido_em !== null ||
                    $segmento->provider_message_id !== null
                ) {
                    throw new RuntimeException(
                        'Existe evidência de segmento iniciado.'
                    );
                }
            }

            /*
            * Não libertar reservas.
            * Serão reutilizadas numa futura tentativa.
            */
            $tentativa->update([
                'estado' => 'CANCELADA_SEM_ENVIO',
                'erro' => 'Tentativa cancelada sem transmissão de PDUs.',
            ]);

            DB::table('sms')
                ->where('id', $sms->id)
                ->update([
                    'proxima_tentativa_em' => now()->addMinutes(5),
                    'updated_at' => now(),
                ]);

            Log::info('SMPP tentativa cancelada sem envio', [
                'sms_id' => $sms->id,
                'tentativa_id' => $tentativaId,
            ]);

            return true;

        }, 3);
    }

}