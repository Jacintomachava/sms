<?php

declare(strict_types=1);

namespace App\Services\SMPP;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SmppReconciliacaoService
{
    private const MAX_VERIFICACOES = 12;

    public function executar(int $limite = 100): array
    {
        $descobertas = $this->descobrirIncertas();

        $resultado = [
            'descobertas' => $descobertas,
            'analisadas' => 0,
            'resolvidas' => 0,
            'reagendadas' => 0,
            'manuais' => 0,
            'erros' => [],
            
        ];

        $ids = DB::table('sms_reconciliacoes')
            ->where('estado', 'PENDENTE')
            ->where(function ($query) {
                $query->whereNull('proxima_verificacao_em')
                    ->orWhere('proxima_verificacao_em', '<=', now());
            })
            ->orderBy('id')
            ->limit(max(1, min($limite, 500)))
            ->pluck('id');

        foreach ($ids as $id) {
            try {
                $estado = $this->verificar((int) $id);

                if ($estado === null) {
                    continue;
                }

                $resultado['analisadas']++;

                if ($estado === 'RESOLVIDA') {
                    $resultado['resolvidas']++;
                } elseif ($estado === 'MANUAL') {
                    $resultado['manuais']++;
                } else {
                    $resultado['reagendadas']++;
                }

            } catch (Throwable $e) {
                $resultado['erros'][] = [
                    'reconciliacao_id' => (int) $id,
                    'erro' => $e->getMessage(),
                ];

                Log::error('Falha na reconciliação SMPP', [
                    'reconciliacao_id' => (int) $id,
                    'erro' => $e->getMessage(),
                ]);
            }
        }

        return $resultado;
    }

    private function verificar(int $id): ?string
    {
        return DB::transaction(function () use ($id): ?string {

            $referencia = DB::table('sms_reconciliacoes')
                ->where('id', $id)
                ->first();

            if (!$referencia) {
                return null;
            }

            /*
             * Ordem de bloqueio:
             * SMS -> reconciliação.
             */
            $sms = DB::table('sms')
                ->where('id', $referencia->sms_id)
                ->lockForUpdate()
                ->first();

            if (!$sms) {
                throw new \RuntimeException(
                    'SMS da reconciliação não encontrada.'
                );
            }

            $reconciliacao = DB::table('sms_reconciliacoes')
                ->where('id', $id)
                ->lockForUpdate()
                ->first();

            if (
                !$reconciliacao ||
                $reconciliacao->estado !== 'PENDENTE'
            ) {
                return null;
            }

            if (
                $reconciliacao->proxima_verificacao_em !== null &&
                $reconciliacao->proxima_verificacao_em > now()->toDateTimeString()
            ) {
                return null;
            }

            $tentativas = (int) $reconciliacao->tentativas + 1;

            /*
             * A SMS já pode ter sido resolvida
             * pelo processamento de uma resposta tardia.
             */
            if (in_array($sms->estado, [
                'ENVIADO',
                'ENTREGUE',
                'REJEITADO',
                'FALHADO',
                'EXPIRADO',
            ], true)) {

                DB::table('sms_reconciliacoes')
                    ->where('id', $id)
                    ->update([
                        'estado' => 'RESOLVIDA',
                        'tentativas' => $tentativas,
                        'resultado' => $sms->estado,
                        'ultima_verificacao_em' => now(),
                        'resolvida_em' => now(),
                        'updated_at' => now(),
                    ]);

                return 'RESOLVIDA';
            }

            /*
             * Não alterar estados financeiros.
             * Apenas consultar a situação dos segmentos.
             */
            $segmentos = DB::table('smpp_segmentos_envio')
                ->where(
                    'smpp_tentativa_envio_id',
                    $reconciliacao->smpp_tentativa_envio_id
                )
                ->get();

            $aceites = $segmentos
                ->where('estado', 'ACEITE')
                ->count();

            $rejeitados = $segmentos
                ->where('estado', 'REJEITADO')
                ->count();

            $incertos = $segmentos
                ->where('estado', 'INCERTO')
                ->count();

            $submetendo = $segmentos
                ->where('estado', 'SUBMETENDO')
                ->count();

            $preparados = $segmentos
                ->where('estado', 'PREPARADO')
                ->count();

            /*
             * A contagem é apenas diagnóstica.
             *
             * Não chamar confirmarAceite nem
             * libertarRejeitada aqui sem validar
             * as condições internas desses serviços.
             */
            $observacao = sprintf(
                'Segmentos: %d aceites, %d rejeitados, %d incertos, %d submetendo, %d preparados.',
                $aceites,
                $rejeitados,
                $incertos,
                $submetendo,
                $preparados
            );

            if ($segmentos->isEmpty()) {
                $observacao = 'Sem segmentos SMPP registados; resultado não comprovado.';
            }

            /*
             * Após 12 verificações, encaminhar
             * para regularização manual.
             */
            if ($tentativas >= self::MAX_VERIFICACOES) {

                DB::table('sms_reconciliacoes')
                    ->where('id', $id)
                    ->update([
                        'estado' => 'MANUAL',
                        'tentativas' => $tentativas,
                        'ultima_verificacao_em' => now(),
                        'proxima_verificacao_em' => null,
                        'observacao' => $observacao,
                        'updated_at' => now(),
                    ]);

                Log::warning(
                    'Reconciliação SMPP encaminhada para análise manual',
                    [
                        'sms_id' => $sms->id,
                        'reconciliacao_id' => $id,
                        'observacao' => $observacao,
                    ]
                );

                return 'MANUAL';
            }

            /*
             * Continuar a verificar posteriormente.
             */
            DB::table('sms_reconciliacoes')
                ->where('id', $id)
                ->update([
                    'tentativas' => $tentativas,
                    'ultima_verificacao_em' => now(),
                    'proxima_verificacao_em' => now()->addMinutes(5),
                    'observacao' => $observacao,
                    'updated_at' => now(),
                ]);

            return 'PENDENTE';

        }, 3);
    }

    /**
     * Descobre SMS incertas ainda sem registo de reconciliação.
     *
     * Não envia mensagens nem altera reservas.
     */
    public function descobrirIncertas(int $limite = 500): int
    {
        $criadas = 0;

        $smsIds = DB::table('sms')
            ->where('estado', 'INCERTO')
            ->whereNotIn('id', function ($query) {
                $query->select('sms_id')
                    ->from('sms_reconciliacoes');
            })
            ->orderBy('id')
            ->limit(max(1, min($limite, 500)))
            ->pluck('id');

        foreach ($smsIds as $smsId) {

            DB::transaction(function () use ($smsId, &$criadas) {

                $sms = DB::table('sms')
                    ->where('id', $smsId)
                    ->lockForUpdate()
                    ->first();

                if (!$sms || $sms->estado !== 'INCERTO') {
                    return;
                }

                $tentativa = DB::table('smpp_tentativas_envio')
                    ->where('sms_id', $smsId)
                    ->orderByDesc('id')
                    ->first();

                $inserida = DB::table('sms_reconciliacoes')
                    ->insertOrIgnore([
                        'sms_id' => $smsId,
                        'smpp_tentativa_envio_id' => $tentativa?->id,
                        'estado' => 'PENDENTE',
                        'tentativas' => 0,
                        'proxima_verificacao_em' => now(),
                        'motivo' => 'DESCOBERTA_AUTOMATICA',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                if ($inserida === 1) {
                    $criadas++;
                }

            }, 3);
        }

        return $criadas;
    }

}