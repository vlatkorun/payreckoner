<?php

declare(strict_types=1);

namespace PayReckoner\Domain\Transaction;

readonly class Transaction
{
    public function __construct(
        public string $id,
        public TransactionType $type,
        public int $amount,
        public Currency $currency,
        public string $merchant,
        public int $timestamp,
    ) {
    }
}
