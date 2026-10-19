<?php

namespace App\Services\SMPP;

use Illuminate\Support\Facades\Log;
use RuntimeException;

class SmppDispatcher
{
    private const SUBMIT_SM_RESP = 0x80000004;
    private const DELIVER_SM = 0x00000005;
    private const ENQUIRE_LINK = 0x00000015;
    private const ENQUIRE_LINK_RESP = 0x80000015;
    private const UNBIND = 0x00000006;

    private array $pendingHeartbeats = [];

    private array $submitResponses = [];

    private int $lastActivityAt;

    public function __construct(
        private readonly SmppConnectionManager $manager,
        private readonly SmppDlrService $dlrService,
        private readonly SmppRespostaService $respostaService
    ) {
        $this->lastActivityAt = time();
    }

    /**
     * Processa a próxima PDU recebida da Movitel.
     *
     * Retorna sem processamento quando ainda não existe
     * uma PDU completa.
     */
    public function processNext(): void
    {
        $connection = $this->manager->getConnection();

        $pdu = $connection->readPdu();

        if ($pdu === null) {
            return;
        }

        $this->lastActivityAt = time();

        $commandId = (int) $pdu['command_id'];
        $sequence = (int) $pdu['sequence'];

        switch ($commandId) {

            /*
            |--------------------------------------------------------------------------
            | 1. HEARTBEAT RECEBIDO
            |--------------------------------------------------------------------------
            */
            case self::ENQUIRE_LINK:

                $connection->respondEnquireLink(
                    $sequence
                );

                break;

            /*
            |--------------------------------------------------------------------------
            | 2. RESPOSTA AO NOSSO HEARTBEAT
            |--------------------------------------------------------------------------
            */
            case self::ENQUIRE_LINK_RESP:

                unset(
                    $this->pendingHeartbeats[$sequence]
                );

                break;

            /*
            |--------------------------------------------------------------------------
            | 3. RELATÓRIO DE ENTREGA
            |--------------------------------------------------------------------------
            */
            case self::DELIVER_SM:

                /*
                 * Primeiro persistimos o relatório.
                 *
                 * Se houver erro na base de dados,
                 * a excepção interrompe o processamento
                 * antes de responder à Movitel.
                 */
                $this->dlrService->processar(
                    $pdu,
                    $this->manager->activeHost()
                );

                /*
                 * Só confirmamos depois de a persistência
                 * terminar com sucesso.
                 */
                $connection->respondDeliverSm(
                    $sequence
                );

                break;

            /*
            |--------------------------------------------------------------------------
            | 4. RESPOSTA DE SUBMISSÃO
            |--------------------------------------------------------------------------
            */
            case self::SUBMIT_SM_RESP:

                $sessionId = $this->manager->sessionId();

                if (!$sessionId) {
                    throw new RuntimeException(
                        'Sessão SMPP não identificada.'
                    );
                }

                $messageId = $this->extractSubmitMessageId(
                    $pdu
                );

                /*
                 * Actualiza a tentativa SMPP e confirma
                 * ou liberta as reservas financeiras.
                 */
                $this->respostaService->processar(
                    sessionId: $sessionId,
                    sequenceNumber: $sequence,
                    commandStatus: (int) $pdu['status'],
                    providerMessageId: $messageId
                );

                /*
                 * Mantemos também a resposta em memória
                 * para compatibilidade com takeSubmitResponse().
                 */
                $this->submitResponses[$sequence] = $pdu;

                break;

            /*
            |--------------------------------------------------------------------------
            | 5. MOVITEL SOLICITOU DESLIGAR
            |--------------------------------------------------------------------------
            */
            case self::UNBIND:

                $connection->respondUnbind(
                    $sequence
                );

                throw new RuntimeException(
                    'Movitel solicitou encerramento da sessão SMPP.'
                );

            /*
            |--------------------------------------------------------------------------
            | 6. OUTROS COMANDOS
            |--------------------------------------------------------------------------
            */
            default:

                Log::warning(
                    'PDU SMPP não tratada',
                    [
                        'command_id' => sprintf(
                            '0x%08X',
                            $commandId
                        ),
                        'sequence' => $sequence,
                    ]
                );

                break;
        }
    }

    /**
     * Envia heartbeat quando a sessão estiver ociosa.
     */
    public function heartbeatIfNeeded(
        int $intervalSeconds = 30
    ): void {
        if (
            time() - $this->lastActivityAt
            < $intervalSeconds
        ) {
            return;
        }

        $connection = $this->manager->getConnection();

        $sequence = $connection->sendEnquireLink();

        $this->pendingHeartbeats[$sequence] = time();

        if (!empty($this->pendingHeartbeats)) {
            return;
        }

        $this->lastActivityAt = time();
    }

    /**
     * Verifica heartbeats sem resposta.
     */
    public function assertHeartbeatHealthy(
        int $timeoutSeconds = 15
    ): void {
        foreach (
            $this->pendingHeartbeats as $sequence => $sentAt
        ) {
            if (
                time() - $sentAt >= $timeoutSeconds
            ) {
                throw new RuntimeException(
                    "Heartbeat SMPP expirado: {$sequence}"
                );
            }
        }
    }

    /**
     * Obtém uma resposta SMPP recebida.
     */
    public function takeSubmitResponse(
        int $sequence
    ): ?array {
        if (
            !array_key_exists(
                $sequence,
                $this->submitResponses
            )
        ) {
            return null;
        }

        $response = $this->submitResponses[$sequence];

        unset($this->submitResponses[$sequence]);

        return $response;
    }

    /**
     * Limpa os dados da sessão anterior.
     */
    public function clearSessionState(): void
    {
        $this->pendingHeartbeats = [];
        $this->submitResponses = [];
        $this->lastActivityAt = time();
    }

    /**
     * Extrai o message_id de submit_sm_resp.
     */
    public function extractSubmitMessageId(
        array $pdu
    ): ?string {
        if ((int) ($pdu['status'] ?? -1) !== 0) {
            return null;
        }

        $body = (string) ($pdu['body'] ?? '');

        $position = strpos($body, "\0");

        if ($position === false) {
            throw new RuntimeException(
                'submit_sm_resp sem terminador do message_id.'
            );
        }

        $messageId = substr(
            $body,
            0,
            $position
        );

        if ($messageId === '') {
            throw new RuntimeException(
                'submit_sm_resp sem message_id.'
            );
        }

        return $messageId;
    }
}
