<?php

declare(strict_types=1);

namespace PayReckoner\Fee;

use PayReckoner\Transaction\Transaction;

class FeeEngine
{
    /**
     * @param Transaction[] $transactions
     * @param FeeRule[]     $rules
     * @return FeeResult[]
     */
    public function process(array $transactions, array $rules): array {}
}
