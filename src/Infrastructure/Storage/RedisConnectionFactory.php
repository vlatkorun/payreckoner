<?php

declare(strict_types=1);

namespace PayReckoner\Infrastructure\Storage;

use PayReckoner\Infrastructure\Config\ConfigurationLoader;

readonly class RedisConnectionFactory
{
    public function __construct(private ConfigurationLoader $config)
    {
    }

    public function create(): \Redis
    {
        $redis = new \Redis();
        $redis->connect(
            (string) $this->config->get('redis.host'),
            (int) $this->config->get('redis.port'),
        );
        $redis->select((int) $this->config->get('redis.database'));

        return $redis;
    }
}
