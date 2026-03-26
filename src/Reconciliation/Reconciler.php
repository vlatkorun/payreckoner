<?php

declare(strict_types=1);

namespace PayReckoner\Reconciliation;

use PayReckoner\Fee\FeeResult;

class Reconciler
{
    /**
     * @param FeeResult[]       $feeResults
     * @param SettlementEntry[] $settlementEntries
     */
    public function reconcile(array $feeResults, array $settlementEntries): ReconciliationReport {}
}
