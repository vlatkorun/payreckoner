<?php

declare(strict_types=1);

namespace PayReckoner\Ledger;

use PayReckoner\Fee\FeeResult;
use PayReckoner\Transaction\Transaction;

class LedgerProcessor
{
    /**
     * @param Transaction[] $transactions
     * @param FeeResult[]   $feeResults
     * @return LedgerEntry[]
     */
    public function build(array $transactions, array $feeResults): array {}
}
