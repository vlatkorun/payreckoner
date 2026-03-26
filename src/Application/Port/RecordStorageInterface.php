<?php

declare(strict_types=1);

namespace PayReckoner\Application\Port;

interface RecordStorageInterface
{
    /**
     * @param list<array<string, mixed>> $records
     */
    public function store(string $key, array $records): void;
}
