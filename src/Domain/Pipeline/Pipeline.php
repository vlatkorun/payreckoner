<?php

declare(strict_types=1);

namespace PayReckoner\Domain\Pipeline;

use PayReckoner\Domain\Fee\FeeEngine;
use PayReckoner\Domain\Fee\FeeRule;
use PayReckoner\Domain\Fraud\FraudEngine;
use PayReckoner\Domain\Ledger\LedgerProcessor;
use PayReckoner\Domain\Reconciliation\Reconciler;
use PayReckoner\Domain\Reconciliation\ReconciliationReport;
use PayReckoner\Domain\Reconciliation\SettlementEntry;
use PayReckoner\Domain\Transaction\Transaction;

class Pipeline
{
    public function __construct(
        private FeeEngine $feeEngine,
        private LedgerProcessor $ledgerProcessor,
        private FraudEngine $fraudEngine,
        private Reconciler $reconciler,
    ) {}

    /**
     * @param Transaction[]     $transactions
     * @param FeeRule[]         $feeRules
     * @param SettlementEntry[] $settlementEntries
     */
    public function run(array $transactions, array $feeRules, array $settlementEntries): PipelineResult {}
}
