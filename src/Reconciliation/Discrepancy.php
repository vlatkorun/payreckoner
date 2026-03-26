<?php

declare(strict_types=1);

namespace PayReckoner\Reconciliation;

readonly class Discrepancy
{
    public function __construct(
        public string $txId,
        public DiscrepancyType $type,
        public ?int $difference,
    ) {}
}
