<?php

declare(strict_types=1);

namespace PayReckoner\Infrastructure\Storage\Redis;

readonly class RedisConnectionFactory
{
    public function __construct(
        private string $host,
        private int $port,
        private int $database,
    ) {
    }

    public function create(): \Redis
    {
        $redis = new \Redis();
        $redis->connect($this->host, $this->port);
        $redis->select($this->database);

        return $redis;
    }
}
