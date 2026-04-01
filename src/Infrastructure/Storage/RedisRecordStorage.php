<?php

declare(strict_types=1);

namespace PayReckoner\Infrastructure\Storage;

use PayReckoner\Application\Port\RecordStorageInterface;
use PayReckoner\Domain\Fee\FeeRule;
use PayReckoner\Domain\Transaction\Transaction;

class RedisRecordStorage implements RecordStorageInterface
{
    private ?\Redis $redis = null;

    public function __construct(private readonly RedisConnectionFactory $connectionFactory)
    {
    }

    /**
     * @param list<array<string, mixed>> $records
     */
    public function store(string $key, array $records): void
    {
        $this->connection()->set($key, json_encode($records, JSON_THROW_ON_ERROR));
    }

    /**
     * @param list<Transaction> $transactions
     */
    public function storeTransactions(array $transactions): void
    {
        $this->store('fixtures:transactions', array_map(
            fn(Transaction $tx) => [
                'id' => $tx->id,
                'type' => $tx->type->value,
                'amount' => $tx->amount,
                'currency' => $tx->currency->value,
                'merchant' => $tx->merchant,
                'timestamp' => $tx->timestamp,
            ],
            $transactions,
        ));
    }

    /**
     * @param list<FeeRule> $feeRules
     */
    public function storeFeeRules(array $feeRules): void
    {
        $this->store('fixtures:fee_rules', array_map(
            fn(FeeRule $rule) => [
                'priority' => $rule->priority,
                'merchant' => $rule->merchant,
                'currency' => $rule->currency?->value,
                'minAmount' => $rule->minAmount,
                'maxAmount' => $rule->maxAmount,
                'feeBps' => $rule->feeBps,
            ],
            $feeRules,
        ));
    }

    private function connection(): \Redis
    {
        return $this->redis ??= $this->connectionFactory->create();
    }
}
