<?php

declare(strict_types=1);

namespace PayReckoner\Domain\Pipeline;

use PayReckoner\Domain\Fee\FeeResult;
use PayReckoner\Domain\Fraud\FraudResult;
use PayReckoner\Domain\Ledger\LedgerEntry;
use PayReckoner\Domain\Reconciliation\ReconciliationReport;

readonly class PipelineResult
{
    public function __construct(
        /** @var FeeResult[] */
        public array $feeResults,
        /** @var LedgerEntry[] */
        public array $ledgerEntries,
        /** @var FraudResult[] */
        public array $fraudResults,
        public ReconciliationReport $reconciliationReport,
    ) {
    }
}
