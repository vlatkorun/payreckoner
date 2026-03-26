<?php

declare(strict_types=1);

namespace PayReckoner\Application\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'generate:fixtures',
    description: 'Generate dummy transaction, fee rule, and settlement JSON files',
)]
class GenerateFixturesCommand extends Command
{
    private const CURRENCIES = ['USD', 'EUR', 'GBP'];
    private const MERCHANTS = ['merchant_alpha', 'merchant_beta', 'merchant_gamma'];

    protected function configure(): void
    {
        $this
            ->addOption('output-dir', 'o', InputOption::VALUE_REQUIRED, 'Directory to write fixture files', '.')
            ->addOption('count', 'c', InputOption::VALUE_REQUIRED, 'Number of transactions to generate', '10');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string $outputDir */
        $outputDir = $input->getOption('output-dir');
        /** @var string $countStr */
        $countStr = $input->getOption('count');
        $count = (int) $countStr;

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0o755, true);
        }

        $transactions = $this->generateTransactions($count);
        $feeRules = $this->generateFeeRules();
        $settlementEntries = $this->generateSettlementEntries($transactions);

        file_put_contents(
            $outputDir . '/transactions.json',
            json_encode($transactions, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
        );
        file_put_contents(
            $outputDir . '/fee_rules.json',
            json_encode($feeRules, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
        );
        file_put_contents(
            $outputDir . '/settlement.json',
            json_encode($settlementEntries, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
        );

        $io->success("Generated {$count} transactions in {$outputDir}/");

        return Command::SUCCESS;
    }

    /**
     * @return list<array{id: string, type: string, amount: int, currency: string, merchant: string, timestamp: int}>
     */
    private function generateTransactions(int $count): array
    {
        $transactions = [];
        $baseTimestamp = 1700000000;

        for ($i = 1; $i <= $count; $i++) {
            $transactions[] = [
                'id' => sprintf('TX-%04d', $i),
                'type' => $i % 3 === 0 ? 'DEBIT' : 'CREDIT',
                'amount' => random_int(100, 500000),
                'currency' => self::CURRENCIES[array_rand(self::CURRENCIES)],
                'merchant' => self::MERCHANTS[array_rand(self::MERCHANTS)],
                'timestamp' => $baseTimestamp + ($i * 60),
            ];
        }

        return $transactions;
    }

    /**
     * @return list<array{priority: int, merchant: ?string, currency: ?string, minAmount: ?int, maxAmount: ?int, feeBps: int}>
     */
    private function generateFeeRules(): array
    {
        return [
            [
                'priority' => 1,
                'merchant' => 'merchant_alpha',
                'currency' => 'USD',
                'minAmount' => 10000,
                'maxAmount' => null,
                'feeBps' => 150,
            ],
            [
                'priority' => 2,
                'merchant' => 'merchant_alpha',
                'currency' => null,
                'minAmount' => null,
                'maxAmount' => null,
                'feeBps' => 200,
            ],
            [
                'priority' => 3,
                'merchant' => null,
                'currency' => 'EUR',
                'minAmount' => null,
                'maxAmount' => 100000,
                'feeBps' => 175,
            ],
            [
                'priority' => 10,
                'merchant' => null,
                'currency' => null,
                'minAmount' => null,
                'maxAmount' => null,
                'feeBps' => 250,
            ],
        ];
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
                // ~10% amount mismatch
                random_int(1, 10) === 1 => $tx['amount'] + random_int(-500, 500),
                default => $tx['amount'],
            };

            $entries[] = [
                'txId' => $tx['id'],
                'settledAmount' => $settledAmount,
                'currency' => $tx['currency'],
            ];
        }

        // Add an EXTRA entry not in transactions
        $entries[] = [
            'txId' => 'TX-EXTRA-001',
            'settledAmount' => 25000,
            'currency' => 'USD',
        ];

        // Remove last real entry to create a MISSING case
        if (count($entries) > 2) {
            array_splice($entries, -2, 1);
        }

        return $entries;
    }
}
