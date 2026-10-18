<?php

namespace App\Services\SMPP;

use Illuminate\Support\Facades\Redis;
use RuntimeException;

class SmppRateLimiter
{
    private const KEY = 'smpp:movitel:rate-limit';

    public function __construct(
        private readonly int $limit = 20
    ) {
        if ($limit < 1) {
            throw new RuntimeException(
                'O limite TPS deve ser maior que zero.'
            );
        }
    }

    /**
     * Reserva uma autorização de submissão SMPP.
     *
     * O limite é partilhado por todos os workers
     * que utilizam a mesma instância Redis.
     *
     * Retorna:
     * true  = submissão autorizada
     * false = limite atingido
     */
    public function acquire(): bool
    {
        $script = <<<'LUA'
        local key = KEYS[1]
        local limit = tonumber(ARGV[1])
        local window = 1000000

        local now = redis.call('TIME')
        local current = tonumber(now[1]) * 1000000
                    + tonumber(now[2])

        redis.call(
            'ZREMRANGEBYSCORE',
            key,
            '-inf',
            current - window
        )

        local count = redis.call('ZCARD', key)

        if count >= limit then
            return 0
        end

        local sequence = redis.call(
            'INCR',
            KEYS[2]
        )

        local member = tostring(current)
            .. ':'
            .. tostring(sequence)

        redis.call(
            'ZADD',
            key,
            current,
            member
        )

        redis.call('PEXPIRE', key, 2000)
        redis.call('PEXPIRE', KEYS[2], 5000)

        return 1
        LUA;

        return (bool) Redis::eval(
            $script,
            2,
            self::KEY,
            self::KEY . ':sequence',
            $this->limit
        );
    }
}
