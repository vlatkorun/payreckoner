<?php

declare(strict_types=1);

namespace PayReckoner\Infrastructure\Storage;

use PayReckoner\Application\Port\RecordStorageInterface;

class RedisRecordStorage implements RecordStorageInterface
{
    private ?\Redis $redis = null;

    public function __construct(private readonly RedisConnectionFactory $connectionFactory) {}

    /**
     * @param list<array<string, mixed>> $records
     */
    public function store(string $key, array $records): void
    {
        $this->connection()->set($key, json_encode($records, JSON_THROW_ON_ERROR));
    }

    private function connection(): \Redis
    {
        return $this->redis ??= $this->connectionFactory->create();
    }
}
