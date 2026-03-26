<?php

declare(strict_types=1);

namespace PayReckoner\Domain\Reconciliation;

use PayReckoner\Domain\Fee\FeeResult;

class Reconciler
{
    /**
     * @param FeeResult[]       $feeResults
     * @param SettlementEntry[] $settlementEntries
     */
    public function reconcile(array $feeResults, array $settlementEntries): ReconciliationReport {}
}
