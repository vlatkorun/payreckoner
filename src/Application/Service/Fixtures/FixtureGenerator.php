<?php

declare(strict_types=1);

namespace PayReckoner\Application\Service\Fixtures;

use PayReckoner\Domain\Fee\FeeRule;
use PayReckoner\Domain\Transaction\Currency;
use PayReckoner\Domain\Transaction\Transaction;
use PayReckoner\Domain\Transaction\TransactionType;

readonly class FixtureGenerator
{
    public function __construct(
        private TransactionFixtureGenerator $transactionGenerator,
        private FeeRuleFixtureGenerator $feeRuleGenerator,
    ) {
    }

    /**
     * @return array{
     *     transactions: list<Transaction>,
     *     feeRules: list<FeeRule>,
     *     settlementEntries: list<array{txId: string, settledAmount: int, currency: string}>,
     * }
     */
    public function generate(int $merchantCount, int $transactionsPerMerchant): array
    {
        $merchants = $this->generateMerchantNames($merchantCount);
        $transactions = $this->transactionGenerator->generate($merchants, $transactionsPerMerchant);
        $feeRules = $this->feeRuleGenerator->generate($merchants);
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
     * @param list<Transaction> $transactions
     * @return list<array{txId: string, settledAmount: int, currency: string}>
     */
    private function generateSettlementEntries(array $transactions): array
    {
        $entries = [];

        foreach ($transactions as $tx) {
            if ($tx->type !== TransactionType::CREDIT) {
                continue;
            }

            $settledAmount = match (true) {
                random_int(1, 10) === 1 => $tx->amount + random_int(-500, 500),
                default => $tx->amount,
            };

            $entries[] = [
                'txId' => $tx->id,
                'settledAmount' => $settledAmount,
                'currency' => $tx->currency->value,
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
