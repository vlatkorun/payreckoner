<?php

declare(strict_types=1);

namespace PayReckoner\Domain\Fee;

readonly class FeeResult
{
    public function __construct(
        public string $txId,
        public int $fee,
        public int $netAmount,
        public ?int $matchedPriority,
    ) {
    }
}
