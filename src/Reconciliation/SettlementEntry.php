<?php

declare(strict_types=1);

namespace PayReckoner\Reconciliation;

readonly class SettlementEntry
{
    public function __construct(
        public string $txId,
        public int $settledAmount,
        public string $currency,
    ) {}
}
