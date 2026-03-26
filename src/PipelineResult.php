<?php

declare(strict_types=1);

namespace PayReckoner;

use PayReckoner\Fraud\FraudResult;
use PayReckoner\Ledger\LedgerEntry;
use PayReckoner\Fee\FeeResult;
use PayReckoner\Reconciliation\ReconciliationReport;

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
    ) {}
}
