<?php

declare(strict_types=1);

namespace PayReckoner\Application\Port;

use PayReckoner\Domain\Fee\FeeRule;
use PayReckoner\Domain\Transaction\Transaction;

interface RecordStorageInterface
{
    /**
     * @param list<array<string, mixed>> $records
     */
    public function store(string $key, array $records): void;

    /**
     * @param list<Transaction> $transactions
     */
    public function storeTransactions(array $transactions): void;

    /**
     * @param list<FeeRule> $feeRules
     */
    public function storeFeeRules(array $feeRules): void;
}
