<?php

declare(strict_types=1);

namespace PayReckoner\Domain\Fee;

use PayReckoner\Domain\Transaction\Transaction;

class FeeEngine
{
    /**
     * @param Transaction[] $transactions
     * @param FeeRule[]     $rules
     * @return FeeResult[]
     */
    public function process(array $transactions, array $rules): array
    {
    }
}
