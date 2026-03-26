<?php

declare(strict_types=1);

namespace PayReckoner\Application\Command;

use PayReckoner\Domain\Fee\FeeEngine;
use PayReckoner\Domain\Fee\FeeRule;
use PayReckoner\Domain\Fraud\FraudEngine;
use PayReckoner\Domain\Ledger\LedgerProcessor;
use PayReckoner\Domain\Pipeline\Pipeline;
use PayReckoner\Domain\Reconciliation\Reconciler;
use PayReckoner\Domain\Reconciliation\SettlementEntry;
use PayReckoner\Domain\Transaction\Transaction;
use PayReckoner\Domain\Transaction\TransactionType;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'pipeline:run',
    description: 'Run the payment processing pipeline on JSON input files',
)]
class RunPipelineCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('transactions', InputArgument::REQUIRED, 'Path to transactions JSON file')
            ->addArgument('fee-rules', InputArgument::REQUIRED, 'Path to fee rules JSON file')
            ->addArgument('settlement', InputArgument::REQUIRED, 'Path to settlement JSON file')
            ->addOption('velocity-limit', null, InputOption::VALUE_REQUIRED, 'Fraud engine velocity limit', '5');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string $transactionsPath */
        $transactionsPath = $input->getArgument('transactions');
        /** @var string $feeRulesPath */
        $feeRulesPath = $input->getArgument('fee-rules');
        /** @var string $settlementPath */
        $settlementPath = $input->getArgument('settlement');
        /** @var string $velocityLimitStr */
        $velocityLimitStr = $input->getOption('velocity-limit');
        $velocityLimit = (int) $velocityLimitStr;

        if (!file_exists($transactionsPath)) {
            $io->error("Transactions file not found: {$transactionsPath}");
            return Command::FAILURE;
        }
        if (!file_exists($feeRulesPath)) {
            $io->error("Fee rules file not found: {$feeRulesPath}");
            return Command::FAILURE;
        }
        if (!file_exists($settlementPath)) {
            $io->error("Settlement file not found: {$settlementPath}");
            return Command::FAILURE;
        }

        $transactions = $this->loadTransactions($transactionsPath);
        $feeRules = $this->loadFeeRules($feeRulesPath);
        $settlementEntries = $this->loadSettlementEntries($settlementPath);

        $pipeline = new Pipeline(
            feeEngine: new FeeEngine(),
            ledgerProcessor: new LedgerProcessor(),
            fraudEngine: new FraudEngine(velocityLimit: $velocityLimit),
            reconciler: new Reconciler(),
        );

        $result = $pipeline->run($transactions, $feeRules, $settlementEntries);

        $io->success('Pipeline completed successfully.');
        $io->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        return Command::SUCCESS;
    }

    /**
     * @return Transaction[]
     */
    private function loadTransactions(string $path): array
    {
        /** @var list<array{id: string, type: string, amount: int, currency: string, merchant: string, timestamp: int}> $data */
        $data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        return array_map(
            static fn(array $row): Transaction => new Transaction(
                id: $row['id'],
                type: TransactionType::from($row['type']),
                amount: $row['amount'],
                currency: $row['currency'],
                merchant: $row['merchant'],
                timestamp: $row['timestamp'],
            ),
            $data,
        );
    }

    /**
     * @return FeeRule[]
     */
    private function loadFeeRules(string $path): array
    {
        /** @var list<array{priority: int, merchant: ?string, currency: ?string, minAmount: ?int, maxAmount: ?int, feeBps: int}> $data */
        $data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        return array_map(
            static fn(array $row): FeeRule => new FeeRule(
                priority: $row['priority'],
                merchant: $row['merchant'] ?? null,
                currency: $row['currency'] ?? null,
                minAmount: $row['minAmount'] ?? null,
                maxAmount: $row['maxAmount'] ?? null,
                feeBps: $row['feeBps'],
            ),
            $data,
        );
    }

    /**
     * @return SettlementEntry[]
     */
    private function loadSettlementEntries(string $path): array
    {
        /** @var list<array{txId: string, settledAmount: int, currency: string}> $data */
        $data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        return array_map(
            static fn(array $row): SettlementEntry => new SettlementEntry(
                txId: $row['txId'],
                settledAmount: $row['settledAmount'],
                currency: $row['currency'],
            ),
            $data,
        );
    }
}
