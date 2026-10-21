<?php

namespace App\Services\SMPP;

use InvalidArgumentException;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SmppService
{
    private const BIND_TRANSCEIVER = 0x00000009;
    private const BIND_TRANSCEIVER_RESP = 0x80000009;
    private const SUBMIT_SM = 0x00000004;
    private const SUBMIT_SM_RESP = 0x80000004;
    private const DELIVER_SM = 0x00000005;
    private const ENQUIRE_LINK = 0x00000015;
    private const UNBIND = 0x00000006;
    private const MAX_PDU_LENGTH = 65536;

    /** @var resource|null */
    private $socket = null;
    private int $sequence = 0;
    private bool $bound = false;
    private string $readBuffer = '';
    /** @var array<int, string> */
    private array $preparedSubmits = [];

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $systemId,
        private readonly string $password,
        private readonly int $timeout = 10
    ) {
        if ($timeout < 1) {
            throw new InvalidArgumentException('Timeout SMPP deve ser maior que zero.');
        }
    }

    public function connect(): void
    {
        if ($this->socket !== null) {
            throw new RuntimeException('Já existe uma ligação SMPP.');
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
            throw new RuntimeException("Falha de ligação SMPP: {$errstr} ({$errno})");
        }

        if (!stream_set_blocking($socket, true) || !stream_set_timeout($socket, $this->timeout)) {
            fclose($socket);
            throw new RuntimeException('Não foi possível configurar o socket SMPP.');
        }

        $this->socket = $socket;
        $this->readBuffer = '';
        $this->preparedSubmits = [];
        $this->bound = false;

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
        $deadline = microtime(true) + $this->timeout;

        do {
            $response = $this->readPdu();
            if ($response !== null) {
                if ($response['command_id'] !== self::BIND_TRANSCEIVER_RESP ||
                    $response['sequence'] !== $sequence) {
                    throw new RuntimeException('Resposta inesperada durante bind SMPP.');
                }

                if ($response['status'] !== 0) {
                    throw new RuntimeException(sprintf(
                        'Bind SMPP recusado. Status: 0x%08X',
                        $response['status']
                    ));
                }

                $this->bound = true;
                return;
            }
        } while (microtime(true) < $deadline);

        throw new RuntimeException('Timeout à espera da resposta de bind SMPP.');
    }

    /**
     * Método legado síncrono. Não utilizar para envios financeiros:
     * só o dispatcher deve processar respostas, DLR e estados de tentativas.
     */
    public function submit(string $sender, string $destination, string $message): array
    {
        throw new RuntimeException(
            'submit() síncrono desactivado: utilize prepareSubmit(), ' .
            'sendPreparedSubmit() e SmppDispatcher para processar a resposta.'
        );
    }

    /**
     * Devolve uma PDU completa ou null quando não há dados suficientes.
     * O buffer é mantido em caso de timeout parcial.
     * Nunca interpretar null como rejeição de uma SMS.
     *
     * @return array{command_id:int,status:int,sequence:int,body:string}|null
     */
    public function readPdu(): ?array
    {
        $this->assertSocket();

        if (!$this->readExact(4)) {
            return null;
        }

        $length = unpack('Nlength', substr($this->readBuffer, 0, 4))['length'];
        if ($length < 16 || $length > self::MAX_PDU_LENGTH) {
            throw new RuntimeException("Tamanho de PDU SMPP inválido: {$length}.");
        }

        if (!$this->readExact($length)) {
            return null;
        }

        $pdu = substr($this->readBuffer, 0, $length);
        $this->readBuffer = substr($this->readBuffer, $length);
        $header = unpack('Ncommand_id/Nstatus/Nsequence', substr($pdu, 4, 12));

        return [
            'command_id' => $header['command_id'],
            'status' => $header['status'],
            'sequence' => $header['sequence'],
            'body' => substr($pdu, 16),
        ];
    }

    private function sendPdu(
        int $commandId,
        int $sequence,
        string $body = '',
        int $status = 0
    ): void {
        $this->assertSocket();
        $length = 16 + strlen($body);
        if ($length > self::MAX_PDU_LENGTH) {
            throw new RuntimeException('PDU SMPP excede o tamanho permitido.');
        }

        $pdu = pack('NNNN', $length, $commandId, $status, $sequence) . $body;
        $offset = 0;
        $total = strlen($pdu);

        while ($offset < $total) {
            $written = @fwrite($this->socket, substr($pdu, $offset));
            if ($written === false || $written === 0) {
                throw new RuntimeException(
                    'Falha ao escrever PDU SMPP; a transmissão pode ter sido parcial.'
                );
            }
            $offset += $written;
        }
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

            $chunk = @fread(
                $this->socket,
                min($remaining, 8192)
            );

            if ($chunk === false || $chunk === '') {

                $metadata = stream_get_meta_data($this->socket);

                if (feof($this->socket) || !empty($metadata['eof'])) {
                    throw new RuntimeException(
                        'Ligação SMPP encerrada durante a leitura.'
                    );
                }

                if (!empty($metadata['timed_out'])) {
                    return false;
                }

                if ($chunk === false) {
                    throw new RuntimeException(
                        'Falha de leitura SMPP sem timeout identificado.'
                    );
                }

                return false;
            }

            $this->readBuffer .= $chunk;
        }

        return true;
    }

    private function assertSocket(): void
    {
        if (!is_resource($this->socket)) {
            throw new RuntimeException('Ligação SMPP indisponível.');
        }
    }

    private function cstring(string $value): string
    {
        if (str_contains($value, "\0")) {
            throw new RuntimeException('CString contém byte nulo.');
        }
        return $value . "\0";
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
        $this->preparedSubmits = [];
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    public function isBound(): bool
    {
        return $this->bound && is_resource($this->socket);
    }

    public function host(): string
    {
        return $this->host;
    }

    public function sendEnquireLink(): int
    {
        if (!$this->isBound()) {
            throw new RuntimeException('Sessão SMPP não autenticada.');
        }
        $sequence = $this->nextSequence();
        $this->sendPdu(self::ENQUIRE_LINK, $sequence);
        return $sequence;
    }

    public function respondEnquireLink(int $sequence): void
    {
        $this->sendPdu(0x80000015, $sequence);
    }

    /** Confirmar deliver_sm apenas depois de persistir o DLR. */
    public function respondDeliverSm(int $sequence, int $status = 0): void
    {
        $this->sendPdu(0x80000005, $sequence, '', $status);
    }

    public function respondUnbind(int $sequence): void
    {
        $this->sendPdu(0x80000006, $sequence);
    }

    /**
     * Prepara um submit_sm GSM7 limitado ao subconjunto ASCII seguro.
     * A PDU não é transmitida neste método.
     */
    public function prepareSubmit(string $sender, string $destination, string $message): int
    {
        if (!$this->isBound()) {
            throw new RuntimeException('Sessão SMPP não autenticada.');
        }
        if ($sender === '' || strlen($sender) > 11 ||
            !preg_match('/^[A-Za-z0-9]+$/', $sender)) {
            throw new InvalidArgumentException('Sender ID inválido.');
        }
        if (!preg_match('/^[0-9]{8,15}$/', $destination)) {
            throw new InvalidArgumentException('Número de destino inválido.');
        }
        if ($message === '' || strlen($message) > 160 ||
            preg_match('/[^\x20-\x7E]/', $message) ||
            strpbrk($message, '@$[]\\^{}|~`') !== false) {
            throw new InvalidArgumentException(
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
            throw new RuntimeException('Sessão SMPP não autenticada.');
        }
        if (!array_key_exists($sequence, $this->preparedSubmits)) {
            throw new RuntimeException('Submissão SMPP não preparada.');
        }

        $body = $this->preparedSubmits[$sequence];
        try {
            $this->sendPdu(self::SUBMIT_SM, $sequence, $body);
        } finally {
            // Mesmo em caso de escrita parcial, nunca reutilizar a submissão.
            unset($this->preparedSubmits[$sequence]);
        }
    }

    public function submitAsync(string $sender, string $destination, string $message): int
    {
        $sequence = $this->prepareSubmit($sender, $destination, $message);
        $this->sendPreparedSubmit($sequence);
        return $sequence;
    }
}
