<?php

declare(strict_types=1);

namespace PayReckoner\Domain\Fraud;

use PayReckoner\Domain\Fee\FeeResult;
use PayReckoner\Domain\Transaction\Transaction;

class FraudEngine
{
    public function __construct(
        private int $velocityLimit,
    ) {
    }

    /**
     * @param Transaction[] $transactions
     * @param FeeResult[]   $feeResults
     * @return FraudResult[]
     */
    public function evaluate(array $transactions, array $feeResults): array
    {
    }
}
