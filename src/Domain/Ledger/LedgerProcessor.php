<?php

declare(strict_types=1);

namespace PayReckoner\Domain\Ledger;

use PayReckoner\Domain\Transaction\Currency;
use PayReckoner\Domain\Transaction\Transaction;
use PayReckoner\Domain\Transaction\TransactionType;

class LedgerProcessor
{
    /**
     * @param Transaction[] $transactions
     * @return LedgerEntry[]
     */
    /**
     * @param Transaction[] $transactions
     * @return LedgerEntry[]
     */
    public function build(array $transactions): array
    {
        $balances = $this->accumulate($transactions);
        $this->sort($balances);

        return $this->toEntries($balances);
    }

    /**
     * @param Transaction[] $transactions
     * @return array<string, array<string, int>>
     */
    private function accumulate(array $transactions): array
    {
        $balances = [];
        $seen = [];

        foreach ($transactions as $tx) {
            if (isset($seen[$tx->id])) {
                throw new \RuntimeException("Duplicate transaction ID: {$tx->id}");
            }
            $seen[$tx->id] = true;

            $key = $tx->currency->value;
            $balances[$tx->merchant][$key] ??= 0;

            $balances[$tx->merchant][$key] += match ($tx->type) {
                TransactionType::CREDIT => $tx->amount,
                TransactionType::DEBIT  => -$tx->amount,
            };
        }

        return $balances;
    }

    /**
     * @param array<string, array<string, int>> $balances
     */
    private function sort(array &$balances): void
    {
        ksort($balances);
        foreach ($balances as &$currencies) {
            ksort($currencies);
        }
    }

    /**
     * @param array<string, array<string, int>> $balances
     * @return LedgerEntry[]
     */
    private function toEntries(array $balances): array
    {
        $entries = [];
        foreach ($balances as $merchant => $currencies) {
            foreach ($currencies as $code => $balance) {
                $entries[] = new LedgerEntry($merchant, Currency::from($code), $balance);
            }
        }

        return $entries;
    }
}
