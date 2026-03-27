<?php

declare(strict_types=1);

namespace PayReckoner\Domain\Ledger;

use PayReckoner\Domain\Transaction\Currency;

readonly class LedgerEntry
{
    public function __construct(
        public string $merchant,
        public Currency $currency,
        public int $balance,
    ) {
    }
}
