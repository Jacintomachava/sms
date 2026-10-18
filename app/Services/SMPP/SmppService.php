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

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $systemId,
        private readonly string $password,
        private readonly int $timeout = 10
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

    public function readPdu(): array
    {
        $headerLength = $this->readExact(4);

        $length = unpack('N', $headerLength)[1];

        if ($length < 16 || $length > 65536) {
            throw new RuntimeException(
                'Tamanho de PDU inválido.'
            );
        }

        $data = $this->readExact($length - 4);

        $header = unpack(
            'Ncommand_id/Nstatus/Nsequence',
            substr($data, 0, 12)
        );

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
    }

    private function readExact(int $length): string
    {
        if (!is_resource($this->socket)) {
            throw new RuntimeException(
                'Ligação SMPP indisponível.'
            );
        }

        $data = '';

        while (strlen($data) < $length) {
            $chunk = @fread(
                $this->socket,
                $length - strlen($data)
            );

            if ($chunk === false || $chunk === '') {
                throw new RuntimeException(
                    'Ligação SMPP interrompida ou timeout.'
                );
            }

            $data .= $chunk;
        }

        return $data;
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

    
    /**
     * Submete uma SMS sem aguardar submit_sm_resp.
     *
     * Retorna o sequence_number da submissão.
     *
     * Esta versão suporta apenas mensagens ASCII
     * de um único segmento, com subconjunto GSM-7.
     */
    public function submitAsync(string $sender, string $destination, string $message): int {

        if (!$this->isBound()) {
            throw new \RuntimeException(
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

        if ($message === '' || strlen($message) > 160 || preg_match('/[^\x20-\x7E]/', $message)) {

            throw new \InvalidArgumentException(
                'Mensagem fora do subconjunto suportado.'
            );
        }

        /*
        * Caracteres da extensão GSM-7 e caracteres
        * ASCII que exigem codificação especial.
        *
        * A implementação completa virá depois.
        */
        if (strpbrk($message, '@$[]\\^{}|~`') !== false) {
            throw new \InvalidArgumentException(
                'Mensagem exige codificação GSM-7 específica.'
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

        $this->sendPdu(self::SUBMIT_SM, $sequence, $body);

        return $sequence;
    }


}
