<?php

namespace App\Services\SMPP;

use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

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

    public function __construct(private readonly SmppConnectionManager $manager) {
        
        $this->lastActivityAt = time();
    }

    /**
     * Processa uma PDU recebida do SMSC.
     *
     * $persistDlr deve guardar o relatório de forma
     * durável antes de devolver o controlo.
     */
   public function processNext(callable $persistDlr, ?callable $processSubmitResponse = null): void {

        $connection = $this->manager->getConnection();

        try {
            $pdu = $connection->readPdu();

            if ($pdu === null) {
                return;
            }

            $this->lastActivityAt = time();

            switch ($pdu['command_id']) {

                case self::ENQUIRE_LINK:
                    $connection->respondEnquireLink(
                        $pdu['sequence']
                    );
                    break;

                case self::ENQUIRE_LINK_RESP:
                    unset(
                        $this->pendingHeartbeats[
                            $pdu['sequence']
                        ]
                    );
                    break;

                case self::DELIVER_SM:
                    /*
                     * O callback recebe a PDU completa.
                     * Ele deve interpretar e persistir
                     * o DLR de forma idempotente.
                     */
                    $persistDlr($pdu);

                    $connection->respondDeliverSm(
                        $pdu['sequence']
                    );
                    break;

                case self::SUBMIT_SM_RESP:
                        if ($processSubmitResponse !== null) {
                            $processSubmitResponse($pdu);
                        } else {
                            $this->submitResponses[$pdu['sequence']] = $pdu;
                        }
                    break;

                case self::UNBIND:
                    $connection->respondUnbind(
                        $pdu['sequence']
                    );

                    throw new RuntimeException(
                        'SMSC solicitou encerramento da sessão.'
                    );

                default:
                    Log::warning(
                        'PDU SMPP não tratada',
                        [
                            'command_id' => sprintf(
                                '0x%08X',
                                $pdu['command_id']
                            ),
                            'sequence' => $pdu['sequence'],
                        ]
                    );
                    break;
            }

        } catch (Throwable $e) {
            /*
             * Nem toda falha deve ser tratada como
             * falha de ligação.
             *
             * Uma falha de BD ao persistir um DLR
             * exige tratamento separado, sem
             * confirmar a recepção.
             */
            throw $e;
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

        $this->lastActivityAt = time();
    }

    /**
     * Detecta heartbeats sem resposta.
     */
    public function assertHeartbeatHealthy(
        int $timeoutSeconds = 15
    ): void {
        foreach (
            $this->pendingHeartbeats as
            $sequence => $sentAt
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
     * Obtém uma resposta de submissão já recebida.
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

    public function clearSessionState(): void
    {
        $this->pendingHeartbeats = [];
        $this->submitResponses = [];
        $this->lastActivityAt = time();
    }

    public function extractSubmitMessageId(array $pdu): ?string
    {
        if ((int) $pdu['status'] !== 0) {
            return null;
        }

        $body = (string) ($pdu['body'] ?? '');

        $position = strpos($body, "\0");

        if ($position === false) {
            throw new RuntimeException(
                'submit_sm_resp sem terminador do message_id.'
            );
        }

        $messageId = substr($body, 0, $position);

        if ($messageId === '') {
            throw new RuntimeException(
                'submit_sm_resp sem message_id.'
            );
        }

        return $messageId;
    }
}
