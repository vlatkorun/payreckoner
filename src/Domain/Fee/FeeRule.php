<?php

declare(strict_types=1);

namespace PayReckoner\Domain\Fee;

use PayReckoner\Domain\Transaction\Currency;

readonly class FeeRule
{
    public function __construct(
        public int $priority,
        public ?string $merchant,
        public ?Currency $currency,
        public ?int $minAmount,
        public ?int $maxAmount,
        public int $feeBps,
    ) {}
}
