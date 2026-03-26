<?php

declare(strict_types=1);

namespace PayReckoner\Domain\Reconciliation;

use PayReckoner\Domain\Transaction\Currency;

readonly class SettlementEntry
{
    public function __construct(
        public string $txId,
        public int $settledAmount,
        public Currency $currency,
    ) {}
}
