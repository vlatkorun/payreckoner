<?php

declare(strict_types=1);

namespace PayReckoner\Domain\Reconciliation;

readonly class ReconciliationSummary
{
    public function __construct(
        public int $totalLedgerCredits,
        public int $totalSettlementEntries,
        public int $matchedCount,
        public int $discrepancyCount,
        /** @var array<string, int> type => count */
        public array $discrepancyBreakdown,
        /** @var array<string, int> currency => disputed amount */
        public array $disputedAmountsByCurrency,
    ) {
    }
}
