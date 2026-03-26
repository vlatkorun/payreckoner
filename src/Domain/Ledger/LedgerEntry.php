<?php

declare(strict_types=1);

namespace PayReckoner\Domain\Ledger;

readonly class LedgerEntry
{
    public function __construct(
        public string $merchant,
        public string $currency,
        public int $balance,
    ) {}
}
