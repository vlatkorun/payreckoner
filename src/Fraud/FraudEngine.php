<?php

declare(strict_types=1);

namespace PayReckoner\Fraud;

use PayReckoner\Fee\FeeResult;
use PayReckoner\Transaction\Transaction;

class FraudEngine
{
    public function __construct(
        private int $velocityLimit,
    ) {}

    /**
     * @param Transaction[] $transactions
     * @param FeeResult[]   $feeResults
     * @return FraudResult[]
     */
    public function evaluate(array $transactions, array $feeResults): array {}
}
