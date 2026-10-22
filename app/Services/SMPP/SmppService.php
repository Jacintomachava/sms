<?php

namespace App\Services\SMPP;

use RuntimeException;
use Throwable;

class SmppService
{
    private const BIND_TRANSCEIVER = 0x00000009;
    private const BIND_TRANSCEIVER_RESP = 0x80000009;
    private const SUBMIT_SM = 0x00000004;
    private const SUBMIT_SM_RESP = 0x80000004;
    private const DELIVER_SM = 0x00000005;
    private const DELIVER_SM_RESP = 0x80000005;
    private const ENQUIRE_LINK = 0x00000015;
    private const ENQUIRE_LINK_RESP = 0x80000015;
    private const UNBIND = 0x00000006;
    private const UNBIND_RESP = 0x80000006;

    private $socket = null;
    private int $sequence = 0;
    private bool $bound = false;
    private array $preparedSubmits = [];

    private string $readBuffer = '';
    private const MAX_PDU_LENGTH = 65536;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $systemId,
        private readonly string $password,
        private readonly int $timeout = 30
    ) {
    }

    public function connect(): void
    {
        if ($this->socket !== null) {
            throw new RuntimeException(
                'Já existe uma ligação SMPP.'
            );
        }

        $errno = 0;
        $errstr = '';

        $socket = @stream_socket_client(
            "tcp://{$this->host}:{$this->port}",
            $errno,
            $errstr,
            $this->timeout,
            STREAM_CLIENT_CONNECT
        );

        if ($socket === false) {
            throw new RuntimeException(
                "Falha de ligação SMPP: {$errstr} ({$errno})"
            );
        }

        stream_set_timeout($socket, $this->timeout);

        $this->socket = $socket;

        try {
            $this->bind();
        } catch (Throwable $e) {
            $this->disconnect();
            throw $e;
        }
    }

    private function bind(): void
    {
        $body =
            $this->cstring($this->systemId) .
            $this->cstring($this->password) .
            $this->cstring('') .
            pack('CCC', 0x34, 0, 0) .
            $this->cstring('');

        $sequence = $this->nextSequence();

        $this->sendPdu(self::BIND_TRANSCEIVER, $sequence, $body);

        $response = $this->readPdu();

        if ($response['command_id'] !== self::BIND_TRANSCEIVER_RESP || $response['sequence'] !== $sequence || $response['status'] !== 0) {
            
            throw new RuntimeException(sprintf(
                'Bind SMPP recusado. Status: 0x%08X',
                $response['status']
            ));
        }

        $this->bound = true;
    }

    /**
     * Envia uma SMS GSM-7 de um único segmento.
     *
     * O texto deve ser ASCII compatível com GSM-7.
     * Não utilizar para Unicode ou mensagens longas.
     */
    public function submit(string $sender, string $destination, string $message): array {
        
        if (!$this->bound) {
            throw new RuntimeException(
                'Sessão SMPP não autenticada.'
            );
        }

        if ($sender === '' || strlen($sender) > 11 || !preg_match('/^[A-Za-z0-9]+$/', $sender)) {
            
            throw new RuntimeException(
                'Sender ID inválido para este envio.'
            );
        }

        if (!preg_match('/^[0-9]{8,15}$/', $destination)) {
            throw new RuntimeException(
                'Número de destino inválido.'
            );
        }

        if ($message === '' || strlen($message) > 160 || preg_match('/[^\x20-\x7E]/', $message)) {
            
            throw new RuntimeException(
                'Esta versão aceita apenas texto ASCII ' .
                'imprimível com até 160 caracteres.'
            );
        }

        // Caracteres ASCII que não podem ser enviados
        // directamente como GSM-7 sem codificação.
        if (strpbrk($message, '@$[]\\^{}|~`') !== false) {
            throw new RuntimeException(
                'A mensagem contém caracteres que exigem ' .
                'codificação GSM-7 específica.'
            );
        }

        $sequence = $this->nextSequence();

        $body =
            $this->cstring('') .
            pack('CC', 5, 0) .
            $this->cstring($sender) .
            pack('CC', 1, 1) .
            $this->cstring($destination) .
            pack('CCC', 0, 0, 0) .
            $this->cstring('') .
            $this->cstring('') .
            pack('CCCC', 1, 0, 0, 0) .
            pack('C', strlen($message)) .
            $message;

        $this->sendPdu(
            self::SUBMIT_SM,
            $sequence,
            $body
        );

        /*
         * Enquanto aguardamos a resposta, o SMSC
         * pode enviar um DLR ou um enquire_link.
         */
        while (true) {
            $response = $this->readPdu();

            if (
                $response['command_id'] ===
                self::ENQUIRE_LINK
            ) {
                $this->sendPdu(
                    self::ENQUIRE_LINK_RESP,
                    $response['sequence']
                );

                continue;
            }

            if (
                $response['command_id'] ===
                self::DELIVER_SM
            ) {
                /*
                 * Não confirmar automaticamente um DLR
                 * sem persistência. Nesta versão, o envio
                 * síncrono não processa DLR intercalado.
                 */
                throw new RuntimeException(
                    'DLR recebido durante submit. ' .
                    'É necessário o dispatcher persistente.'
                );
            }

            if (
                $response['command_id'] !==
                    self::SUBMIT_SM_RESP ||
                $response['sequence'] !== $sequence
            ) {
                throw new RuntimeException(
                    'Resposta SMPP inesperada.'
                );
            }

            $messageId = $this->readCString(
                $response['body']
            );

            return [
                'accepted' => $response['status'] === 0,
                'status' => $response['status'],
                'message_id' => $messageId,
                'sequence' => $sequence,
                'host' => $this->host,
            ];
        }
    }

    public function readPdu(): ?array
    {
        if (!is_resource($this->socket)) {
            throw new RuntimeException(
                'Ligação SMPP indisponível.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 1. LER O CABEÇALHO DO TAMANHO
        |--------------------------------------------------------------------------
        */
        if (!$this->readExact(4)) {
            return null;
        }

        $length = unpack('N', substr($this->readBuffer, 0, 4))[1];

        if ($length < 16 || $length > self::MAX_PDU_LENGTH) {
            throw new RuntimeException(
                'Tamanho de PDU SMPP inválido.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 2. AGUARDAR A PDU COMPLETA
        |--------------------------------------------------------------------------
        */
        if (!$this->readExact($length)) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | 3. EXTRAIR A PDU DO BUFFER
        |--------------------------------------------------------------------------
        */
        $data = substr($this->readBuffer, 4, $length - 4);

        $this->readBuffer = substr($this->readBuffer, $length);

        /*
        |--------------------------------------------------------------------------
        | 4. INTERPRETAR CABEÇALHO SMPP
        |--------------------------------------------------------------------------
        */
        $header = unpack('Ncommand_id/Nstatus/Nsequence', substr($data, 0, 12));

        return [
            'command_id' => $header['command_id'],
            'status' => $header['status'],
            'sequence' => $header['sequence'],
            'body' => substr($data, 12),
        ];
    }

    private function sendPdu(
        int $commandId,
        int $sequence,
        string $body = '',
        int $status = 0
    ): void {

        $length = 16 + strlen($body);

        $pdu = pack(
            'NNNN',
            $length,
            $commandId,
            $status,
            $sequence
        ) . $body;

        // LOG 1: antes de escrever a PDU
        \Illuminate\Support\Facades\Log::info(
            'SMPP PDU ENVIO INICIADO',
            [
                'host' => $this->host,
                'command_id' => sprintf('0x%08X', $commandId),
                'sequence_number' => $sequence,
                'command_status' => $status,
                'pdu_length' => $length,
            ]
        );

        $offset = 0;
        $total = strlen($pdu);

        while ($offset < $total) {

            $written = @fwrite(
                $this->socket,
                substr($pdu, $offset)
            );

            if ($written === false || $written === 0) {
                throw new RuntimeException(
                    'Falha ao escrever PDU SMPP.'
                );
            }

            $offset += $written;
        }

        // LOG 2: depois de escrever a PDU completa
        \Illuminate\Support\Facades\Log::info(
            'SMPP PDU ESCRITA COMPLETAMENTE',
            [
                'host' => $this->host,
                'command_id' => sprintf('0x%08X', $commandId),
                'sequence_number' => $sequence,
                'bytes_escritos' => $offset,
            ]
        );
    }

    private function readExact(int $length): bool
    {
        if (!is_resource($this->socket)) {
            throw new RuntimeException(
                'Ligação SMPP indisponível.'
            );
        }

        while (strlen($this->readBuffer) < $length) {

            $remaining = $length - strlen($this->readBuffer);

            $read = [$this->socket];
            $write = null;
            $except = null;

            // Esperar no máximo 1 segundo por dados.
            $selectError = null;
            set_error_handler(static function (int $severity, string $message) use (&$selectError): bool {
                $selectError = $message;
                return true;
            });
            try {
                $ready = stream_select($read, $write, $except, 1, 0);
            } finally {
                restore_error_handler();
            }

            if ($ready === false) {
                // SIGTERM/SIGINT pode interromper select(2). O ciclo exterior
                // verifica a flag de paragem antes de qualquer nova submissão.
                if ($selectError !== null && (
                    stripos($selectError, 'Interrupted system call') !== false ||
                    stripos($selectError, 'EINTR') !== false
                )) {
                    return false;
                }
                throw new RuntimeException(
                    'Erro ao verificar disponibilidade do socket SMPP: ' .
                    ($selectError ?: 'erro desconhecido')
                );
            }

            if ($ready === 0) {
                // Nenhum dado disponível.
                return false;
            }

            $readError = null;

            set_error_handler(
                static function (
                    int $severity,
                    string $message
                ) use (&$readError): bool {
                    $readError = $message;
                    return true;
                }
            );

            try {
                $chunk = fread(
                    $this->socket,
                    min($remaining, 8192)
                );
            } finally {
                restore_error_handler();
            }

            if ($chunk === false) {

                $metadata = stream_get_meta_data($this->socket);

                \Illuminate\Support\Facades\Log::error(
                    'SMPP FALHA NA LEITURA',
                    [
                        'host' => $this->host,
                        'porta' => $this->port,
                        'erro_php' => $readError,
                        'timed_out' => $metadata['timed_out'] ?? null,
                        'eof' => $metadata['eof'] ?? null,
                        'bytes_esperados' => $length,
                        'bytes_buffer' => strlen($this->readBuffer),
                    ]
                );

                throw new RuntimeException(
                    'Erro na leitura do socket SMPP: ' .
                    ($readError ?: 'erro desconhecido')
                );
            }

            if ($chunk === '') {

                if (feof($this->socket)) {
                    throw new RuntimeException(
                        'Ligação SMPP encerrada pela Movitel.'
                    );
                }

                return false;
            }

            $this->readBuffer .= $chunk;
        }

        return true;
    }

    private function cstring(string $value): string
    {
        if (str_contains($value, "\0")) {
            throw new RuntimeException(
                'CString contém byte nulo.'
            );
        }

        return $value . "\0";
    }

    private function readCString(string $value): string
    {
        $position = strpos($value, "\0");

        return $position === false
            ? $value
            : substr($value, 0, $position);
    }

    private function nextSequence(): int
    {
        $this->sequence++;

        if ($this->sequence > 0x7FFFFFFF) {
            $this->sequence = 1;
        }

        return $this->sequence;
    }

    public function disconnect(): void
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
        }

        $this->socket = null;
        $this->bound = false;
        $this->readBuffer = '';
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    
    /**
     * Verifica se existe uma sessão autenticada.
     *
     * Não garante que a ligação remota ainda esteja viva.
     */
    public function isBound(): bool
    {
        return $this->bound && is_resource($this->socket);
    }

    /**
     * Retorna o IP da ligação.
     */
    public function host(): string
    {
        return $this->host;
    }

    /**
     * Envia enquire_link e devolve o sequence_number.
     */
    public function sendEnquireLink(): int
    {
        if (!$this->isBound()) {
            throw new \RuntimeException(
                'Sessão SMPP não autenticada.'
            );
        }

        $sequence = $this->nextSequence();

        $this->sendPdu(0x00000015, $sequence);

        return $sequence;
    }

    /**
     * Responde ao heartbeat recebido do SMSC.
     */
    public function respondEnquireLink(int $sequence): void
    {
        $this->sendPdu(0x80000015, $sequence);
    }

    /**
     * Confirma a recepção de um deliver_sm.
     *
     * Chamar somente após persistir o DLR.
     */
    public function respondDeliverSm(int $sequence, int $status = 0): void {

        $this->sendPdu(0x80000005, $sequence, '', $status);
    }

    /**
     * Responde a um unbind recebido.
     */
    public function respondUnbind(int $sequence): void
    {
        $this->sendPdu(0x80000006, $sequence);
    }

    public function prepareSubmit(string $sender, string $destination, string $message): int {

        if (!$this->isBound()) {
            throw new RuntimeException(
                'Sessão SMPP não autenticada.'
            );
        }

        if ($sender === '' || strlen($sender) > 11 || !preg_match('/^[A-Za-z0-9]+$/', $sender)) {
            throw new \InvalidArgumentException(
                'Sender ID inválido.'
            );
        }

        if (!preg_match('/^[0-9]{8,15}$/', $destination)) {
            throw new \InvalidArgumentException(
                'Número de destino inválido.'
            );
        }

        if ($message === '' || strlen($message) > 160 || preg_match('/[^\x20-\x7E]/', $message) || strpbrk($message, '@$[]\\^{}|~`') !== false) {
            throw new \InvalidArgumentException(
                'Mensagem fora do subconjunto GSM-7 suportado.'
            );
        }

        $sequence = $this->nextSequence();

        $body =
            $this->cstring('') .
            pack('CC', 5, 0) .
            $this->cstring($sender) .
            pack('CC', 1, 1) .
            $this->cstring($destination) .
            pack('CCC', 0, 0, 0) .
            $this->cstring('') .
            $this->cstring('') .
            pack('CCCC', 1, 0, 0, 0) .
            pack('C', strlen($message)) .
            $message;

        $this->preparedSubmits[$sequence] = $body;

        return $sequence;
    }

    public function sendPreparedSubmit(int $sequence): void
    {
        if (!$this->isBound()) {
            throw new RuntimeException(
                'Sessão SMPP não autenticada.'
            );
        }

        if (!array_key_exists($sequence, $this->preparedSubmits)) {
            throw new RuntimeException(
                'Submissão SMPP não preparada.'
            );
        }

        $body = $this->preparedSubmits[$sequence];

        try {
            $this->sendPdu(self::SUBMIT_SM, $sequence, $body);

        } finally {
            // Não reutilizar esta submissão após tentativa de escrita.
            unset($this->preparedSubmits[$sequence]);
        }
    }

    public function submitAsync(string $sender, string $destination, string $message): int {

        $sequence = $this->prepareSubmit($sender, $destination, $message);

        $this->sendPreparedSubmit($sequence);

        return $sequence;
    }

}
