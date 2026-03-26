<?php

declare(strict_types=1);

namespace PayReckoner\Domain\Ledger;

use PayReckoner\Domain\Fee\FeeResult;
use PayReckoner\Domain\Transaction\Transaction;

class LedgerProcessor
{
    /**
     * @param Transaction[] $transactions
     * @param FeeResult[]   $feeResults
     * @return LedgerEntry[]
     */
    public function build(array $transactions, array $feeResults): array {}
}
