<?php

declare(strict_types=1);

namespace PayReckoner\Tests\Unit\Domain\Ledger;

use PayReckoner\Domain\Ledger\LedgerEntry;
use PayReckoner\Domain\Ledger\LedgerProcessor;
use PayReckoner\Domain\Transaction\Currency;
use PayReckoner\Domain\Transaction\Transaction;
use PayReckoner\Domain\Transaction\TransactionType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LedgerProcessorTest extends TestCase
{
    private LedgerProcessor $processor;

    protected function setUp(): void
    {
        $this->processor = new LedgerProcessor();
    }

    /**
     * @param Transaction[] $transactions
     * @param LedgerEntry[] $expected
     */
    #[DataProvider('buildProvider')]
    public function testBuild(array $transactions, array $expected): void
    {
        self::assertEquals($expected, $this->processor->build($transactions));
    }

    public static function buildProvider(): \Generator
    {
        yield 'empty input' => [
            [],
            [],
        ];

        yield 'single credit' => [
            [
                new Transaction('tx1', TransactionType::CREDIT, 5000, Currency::USD, 'acme', 1000),
            ],
            [
                new LedgerEntry('acme', Currency::USD, 5000),
            ],
        ];

        yield 'single debit' => [
            [
                new Transaction('tx1', TransactionType::DEBIT, 3000, Currency::USD, 'acme', 1000),
            ],
            [
                new LedgerEntry('acme', Currency::USD, -3000),
            ],
        ];

        yield 'credit and debit same merchant and currency' => [
            [
                new Transaction('tx1', TransactionType::CREDIT, 5000, Currency::USD, 'acme', 1000),
                new Transaction('tx2', TransactionType::DEBIT, 2000, Currency::USD, 'acme', 1001),
            ],
            [
                new LedgerEntry('acme', Currency::USD, 3000),
            ],
        ];

        yield 'zero balance' => [
            [
                new Transaction('tx1', TransactionType::CREDIT, 5000, Currency::USD, 'acme', 1000),
                new Transaction('tx2', TransactionType::DEBIT, 5000, Currency::USD, 'acme', 1001),
            ],
            [
                new LedgerEntry('acme', Currency::USD, 0),
            ],
        ];

        yield 'negative balance — debit only' => [
            [
                new Transaction('tx1', TransactionType::DEBIT, 5000, Currency::USD, 'acme', 1000),
            ],
            [
                new LedgerEntry('acme', Currency::USD, -5000),
            ],
        ];

        yield 'negative balance — debits exceed credits' => [
            [
                new Transaction('tx1', TransactionType::CREDIT, 1000, Currency::USD, 'acme', 1000),
                new Transaction('tx2', TransactionType::DEBIT, 4000, Currency::USD, 'acme', 1001),
            ],
            [
                new LedgerEntry('acme', Currency::USD, -3000),
            ],
        ];

        yield 'multiple transactions accumulate correctly' => [
            [
                new Transaction('tx1', TransactionType::CREDIT, 1000, Currency::USD, 'acme', 1000),
                new Transaction('tx2', TransactionType::CREDIT, 2000, Currency::USD, 'acme', 1001),
                new Transaction('tx3', TransactionType::CREDIT, 3000, Currency::USD, 'acme', 1002),
                new Transaction('tx4', TransactionType::DEBIT, 1500, Currency::USD, 'acme', 1003),
            ],
            [
                new LedgerEntry('acme', Currency::USD, 4500),
            ],
        ];

        yield 'multiple merchants sorted ascending' => [
            [
                new Transaction('tx1', TransactionType::CREDIT, 1000, Currency::USD, 'zeta', 1000),
                new Transaction('tx2', TransactionType::CREDIT, 2000, Currency::USD, 'acme', 1001),
                new Transaction('tx3', TransactionType::CREDIT, 3000, Currency::USD, 'mango', 1002),
            ],
            [
                new LedgerEntry('acme', Currency::USD, 2000),
                new LedgerEntry('mango', Currency::USD, 3000),
                new LedgerEntry('zeta', Currency::USD, 1000),
            ],
        ];

        yield 'multiple currencies within merchant sorted ascending' => [
            [
                new Transaction('tx1', TransactionType::CREDIT, 1000, Currency::USD, 'acme', 1000),
                new Transaction('tx2', TransactionType::CREDIT, 2000, Currency::EUR, 'acme', 1001),
                new Transaction('tx3', TransactionType::CREDIT, 3000, Currency::GBP, 'acme', 1002),
            ],
            [
                new LedgerEntry('acme', Currency::EUR, 2000),
                new LedgerEntry('acme', Currency::GBP, 3000),
                new LedgerEntry('acme', Currency::USD, 1000),
            ],
        ];

        yield 'multiple merchants and currencies sorted by merchant then currency' => [
            [
                new Transaction('tx1', TransactionType::CREDIT, 1000, Currency::USD, 'zeta', 1000),
                new Transaction('tx2', TransactionType::CREDIT, 2000, Currency::EUR, 'acme', 1001),
                new Transaction('tx3', TransactionType::CREDIT, 3000, Currency::USD, 'acme', 1002),
            ],
            [
                new LedgerEntry('acme', Currency::EUR, 2000),
                new LedgerEntry('acme', Currency::USD, 3000),
                new LedgerEntry('zeta', Currency::USD, 1000),
            ],
        ];

        yield 'currencies are never summed across merchants' => [
            [
                new Transaction('tx1', TransactionType::CREDIT, 1000, Currency::USD, 'acme', 1000),
                new Transaction('tx2', TransactionType::CREDIT, 2000, Currency::USD, 'zeta', 1001),
            ],
            [
                new LedgerEntry('acme', Currency::USD, 1000),
                new LedgerEntry('zeta', Currency::USD, 2000),
            ],
        ];
    }

    public function testDuplicateTransactionIdThrows(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/tx1/');

        $this->processor->build([
            new Transaction('tx1', TransactionType::CREDIT, 1000, Currency::USD, 'acme', 1000),
            new Transaction('tx1', TransactionType::CREDIT, 2000, Currency::USD, 'acme', 1001),
        ]);
    }
}
