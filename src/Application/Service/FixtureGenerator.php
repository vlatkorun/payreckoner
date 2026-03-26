<?php

declare(strict_types=1);

namespace PayReckoner\Application\Service;

use PayReckoner\Domain\Transaction\Currency;

readonly class FixtureGenerator
{
    /**
     * @return array{
     *     transactions: list<array{id: string, type: string, amount: int, currency: string, merchant: string, timestamp: int}>,
     *     feeRules: list<array{priority: int, merchant: ?string, currency: ?string, minAmount: ?int, maxAmount: ?int, feeBps: int}>,
     *     settlementEntries: list<array{txId: string, settledAmount: int, currency: string}>,
     * }
     */
    public function generate(int $merchantCount, int $transactionsPerMerchant): array
    {
        $merchants = $this->generateMerchantNames($merchantCount);
        $transactions = $this->generateTransactions($merchants, $transactionsPerMerchant);
        $feeRules = $this->generateFeeRules($merchants);
        $settlementEntries = $this->generateSettlementEntries($transactions);

        return [
            'transactions' => $transactions,
            'feeRules' => $feeRules,
            'settlementEntries' => $settlementEntries,
        ];
    }

    /**
     * @return list<string>
     */
    private function generateMerchantNames(int $count): array
    {
        $merchants = [];
        for ($i = 1; $i <= $count; $i++) {
            $merchants[] = sprintf('merchant_%d', $i);
        }

        return $merchants;
    }

    /**
     * @param list<string> $merchants
     * @return list<array{id: string, type: string, amount: int, currency: string, merchant: string, timestamp: int}>
     */
    private function generateTransactions(array $merchants, int $perMerchant): array
    {
        $transactions = [];
        $currencies = Currency::cases();
        $baseTimestamp = 1700000000;
        $counter = 0;

        foreach ($merchants as $merchant) {
            for ($i = 1; $i <= $perMerchant; $i++) {
                $counter++;
                $transactions[] = [
                    'id' => sprintf('TX-%04d', $counter),
                    'type' => $counter % 3 === 0 ? 'DEBIT' : 'CREDIT',
                    'amount' => random_int(100, 500000),
                    'currency' => $currencies[array_rand($currencies)]->value,
                    'merchant' => $merchant,
                    'timestamp' => $baseTimestamp + ($counter * 60),
                ];
            }
        }

        return $transactions;
    }

    /**
     * @param list<string> $merchants
     * @return list<array{priority: int, merchant: ?string, currency: ?string, minAmount: ?int, maxAmount: ?int, feeBps: int}>
     */
    private function generateFeeRules(array $merchants): array
    {
        $rules = [];
        $priority = 0;

        $firstMerchant = $merchants[0] ?? null;
        if ($firstMerchant !== null) {
            $rules[] = [
                'priority' => ++$priority,
                'merchant' => $firstMerchant,
                'currency' => Currency::USD->value,
                'minAmount' => 10000,
                'maxAmount' => null,
                'feeBps' => 150,
            ];
            $rules[] = [
                'priority' => ++$priority,
                'merchant' => $firstMerchant,
                'currency' => null,
                'minAmount' => null,
                'maxAmount' => null,
                'feeBps' => 200,
            ];
        }

        $rules[] = [
            'priority' => ++$priority,
            'merchant' => null,
            'currency' => Currency::EUR->value,
            'minAmount' => null,
            'maxAmount' => 100000,
            'feeBps' => 175,
        ];

        $rules[] = [
            'priority' => ++$priority,
            'merchant' => null,
            'currency' => null,
            'minAmount' => null,
            'maxAmount' => null,
            'feeBps' => 250,
        ];

        return $rules;
    }

    /**
     * @param list<array{id: string, type: string, amount: int, currency: string, merchant: string, timestamp: int}> $transactions
     * @return list<array{txId: string, settledAmount: int, currency: string}>
     */
    private function generateSettlementEntries(array $transactions): array
    {
        $entries = [];

        foreach ($transactions as $tx) {
            if ($tx['type'] !== 'CREDIT') {
                continue;
            }

            $settledAmount = match (true) {
                random_int(1, 10) === 1 => $tx['amount'] + random_int(-500, 500),
                default => $tx['amount'],
            };

            $entries[] = [
                'txId' => $tx['id'],
                'settledAmount' => $settledAmount,
                'currency' => $tx['currency'],
            ];
        }

        $entries[] = [
            'txId' => 'TX-EXTRA-001',
            'settledAmount' => 25000,
            'currency' => Currency::USD->value,
        ];

        if (count($entries) > 2) {
            array_splice($entries, -2, 1);
        }

        return $entries;
    }
}
