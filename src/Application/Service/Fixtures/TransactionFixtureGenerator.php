<?php

declare(strict_types=1);

namespace PayReckoner\Application\Service\Fixtures;

use PayReckoner\Domain\Transaction\Currency;
use PayReckoner\Domain\Transaction\Transaction;
use PayReckoner\Domain\Transaction\TransactionType;

readonly class TransactionFixtureGenerator
{
    /**
     * @param list<string> $merchants
     * @return list<Transaction>
     */
    public function generate(array $merchants, int $perMerchant): array
    {
        $transactions = [];
        $currencies = Currency::cases();
        $baseTimestamp = 1700000000;
        $counter = 0;

        foreach ($merchants as $merchant) {
            for ($i = 1; $i <= $perMerchant; $i++) {
                $counter++;
                $transactions[] = new Transaction(
                    id: sprintf('TX-%04d', $counter),
                    type: $counter % 3 === 0 ? TransactionType::DEBIT : TransactionType::CREDIT,
                    amount: random_int(100, 500000),
                    currency: $currencies[array_rand($currencies)],
                    merchant: $merchant,
                    timestamp: $baseTimestamp + ($counter * 60),
                );
            }
        }

        return $transactions;
    }
}
