<?php

declare(strict_types=1);

namespace PayReckoner\Reconciliation;

readonly class ReconciliationReport
{
    public function __construct(
        /** @var Discrepancy[] */
        public array $discrepancies,
        public ReconciliationSummary $summary,
    ) {}
}
