<?php

namespace App\Services\SMPP;

use App\Models\SmppEventoConexao;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class SmppConnectionManager
{
    private ?SmppService $connection = null;

    private ?string $activeHost = null;
    private ?string $sessionId = null;

    private array $hosts;

    private int $port;
    private int $timeout;
    private int $reconnectDelay;

    private string $systemId;
    private string $password;
    private string $workerId;

    private ?int $lastAttemptAt = null;

    public function __construct()
    {
        $config = config('services.smpp', []);

        $this->hosts = array_values(array_unique($config['hosts'] ?? []));
        $this->port = (int) ($config['port'] ?? 8014);
        $this->timeout = (int) ($config['timeout'] ?? 10);
        $this->reconnectDelay = max(1, (int) ($config['reconnect_delay'] ?? 10));
        $this->systemId = (string) ($config['system_id'] ?? '');
        $this->password = (string) ($config['password'] ?? '');
        $this->workerId = gethostname() . ':' . getmypid();

        if (empty($this->hosts)) {
            throw new RuntimeException(
                'Nenhum servidor SMPP configurado.'
            );
        }

        if ($this->systemId === '' || $this->password === '') {
            throw new RuntimeException(
                'Credenciais SMPP não configuradas.'
            );
        }
    }

    /**
     * Obtém a ligação activa ou tenta estabelecer uma.
     */
    public function getConnection(): SmppService
    {
        if ($this->connection !== null) {
            return $this->connection;
        }

        return $this->connect();
    }

    /**
     * Tenta os IPs configurados, por ordem.
     */
    public function connect(): SmppService
    {
        if ($this->connection !== null) {
            return $this->connection;
        }

        if ($this->lastAttemptAt !== null && time() - $this->lastAttemptAt < $this->reconnectDelay) {

            throw new RuntimeException(
                'Aguardar intervalo de reconexão SMPP.'
            );
        }

        $this->lastAttemptAt = time();

        $previousHost = $this->activeHost;

        foreach ($this->hosts as $host) {
            $client = new SmppService(
                host: $host,
                port: $this->port,
                systemId: $this->systemId,
                password: $this->password,
                timeout: $this->timeout
            );

            try {

                $client->connect();

                $this->connection = $client;
                $this->activeHost = $host;
                $this->sessionId = (string) Str::uuid();

                $this->recordEvent(
                    host: $host,
                    type: 'CONECTADO',
                    description: 'Ligação SMPP estabelecida.'
                );

                if ($previousHost !== null && $previousHost !== $host) {
                   
                    $this->recordEvent(
                        host: $host,
                        type: 'FAILOVER',
                        previousHost: $previousHost,
                        newHost: $host,
                        description: 'Mudança automática de servidor SMPP.'
                    );
                }

                Log::info('SMPP conectado', [
                    'host' => $host,
                    'worker_id' => $this->workerId,
                    'manager_object_id' => spl_object_id($this),
                    'session_id' => $this->sessionId,
                ]);

                return $client;

            } catch (Throwable $e) {
                $client->disconnect();

                $this->recordEvent(
                    host: $host,
                    type: 'FALHA',
                    description: $e->getMessage()
                );

                Log::warning('Falha de ligação SMPP', [
                    'host' => $host,
                    'erro' => $e->getMessage(),
                ]);
            }
        }

        $this->recordEvent(
            host: $this->hosts[0],
            type: 'TODOS_INDISPONIVEIS',
            description: 'Não foi possível estabelecer ligação SMPP.'
        );

        throw new RuntimeException(
            'Todos os servidores SMPP estão indisponíveis.'
        );
    }

    /**
     * Chamado quando a ligação activa apresenta falha.
     *
     * A decisão sobre o estado INCERTO de uma SMS
     * pertence ao worker, não ao gestor de conexões.
     */
    public function reportFailure(Throwable $exception): void
    {
        $failedHost = $this->activeHost;

        if ($failedHost !== null) {
            $this->recordEvent(
                host: $failedHost,
                type: 'FALHA',
                description: $exception->getMessage()
            );
        }

        $this->connection?->disconnect();
        $this->connection = null;
        $this->sessionId = null;

        /*
         * Mantemos activeHost para registar a
         * transição quando houver nova ligação.
         */
    }

    public function disconnect(): void
    {
        if ($this->connection !== null) {
            $this->connection->disconnect();

            if ($this->activeHost !== null) {
                $this->recordEvent(
                    host: $this->activeHost,
                    type: 'DESCONECTADO',
                    description: 'Ligação SMPP encerrada.'
                );
            }
        }

        $this->connection = null;
        $this->activeHost = null;
        $this->sessionId = null;
    }

    public function activeHost(): ?string
    {
        return $this->activeHost;
    }

    public function workerId(): string
    {
        return $this->workerId;
    }

    public function sessionId(): ?string
    {
        return $this->sessionId;
    }

    private function recordEvent(string $host, string $type, ?string $description = null, ?string $previousHost = null, ?string $newHost = null): void {
        
        try {
            
            SmppEventoConexao::create([
                'host' => $host,
                'porta' => $this->port,
                'tipo' => $type,
                'host_anterior' => $previousHost,
                'host_novo' => $newHost,
                'descricao' => $description,
                'worker_id' => $this->workerId,
                'ocorrido_em' => now(),
            ]);
        } catch (Throwable $e) {
            /*
             * Uma falha ao registar o evento não deve
             * impedir a recuperação da ligação SMPP.
             */
            Log::error(
                'Falha ao registar evento SMPP',
                ['erro' => $e->getMessage()]
            );
        }
    }
}
