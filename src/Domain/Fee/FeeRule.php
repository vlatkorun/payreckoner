<?php

declare(strict_types=1);

namespace PayReckoner\Domain\Fee;

readonly class FeeRule
{
    public function __construct(
        public int $priority,
        public ?string $merchant,
        public ?string $currency,
        public ?int $minAmount,
        public ?int $maxAmount,
        public int $feeBps,
    ) {}
}
