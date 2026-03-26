<?php

declare(strict_types=1);

namespace PayReckoner\Domain\Fraud;

use PayReckoner\Domain\Transaction\TransactionType;

readonly class FraudResult
{
    public function __construct(
        public string $txId,
        public TransactionType $type,
        public string $merchant,
        public int $timestamp,
        /** @var FraudFlag[] */
        public array $flags,
    ) {}
}
