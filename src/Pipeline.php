<?php

declare(strict_types=1);

namespace PayReckoner;

use PayReckoner\Fee\FeeEngine;
use PayReckoner\Fee\FeeRule;
use PayReckoner\Fraud\FraudEngine;
use PayReckoner\Ledger\LedgerProcessor;
use PayReckoner\Reconciliation\Reconciler;
use PayReckoner\Reconciliation\ReconciliationReport;
use PayReckoner\Reconciliation\SettlementEntry;
use PayReckoner\Transaction\Transaction;

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
