<?php

declare(strict_types=1);

namespace PayReckoner\Application\Command;

use PayReckoner\Application\Port\RecordStorageInterface;
use PayReckoner\Application\Service\FixtureGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'generate:fixtures',
    description: 'Generate dummy transaction, fee rule, and settlement records',
)]
class GenerateFixturesCommand extends Command
{
    public function __construct(
        private readonly FixtureGenerator $generator,
        private readonly RecordStorageInterface $storage,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('merchants', InputArgument::REQUIRED, 'Number of merchants to generate')
            ->addOption('count', 'c', InputOption::VALUE_REQUIRED, 'Number of transactions per merchant', '10');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string $merchantsStr */
        $merchantsStr = $input->getArgument('merchants');
        $merchantCount = (int) $merchantsStr;

        /** @var string $countStr */
        $countStr = $input->getOption('count');
        $transactionsPerMerchant = (int) $countStr;

        $fixtures = $this->generator->generate($merchantCount, $transactionsPerMerchant);

        $this->storage->store('fixtures:transactions', $fixtures['transactions']);
        $this->storage->store('fixtures:fee_rules', $fixtures['feeRules']);
        $this->storage->store('fixtures:settlement_entries', $fixtures['settlementEntries']);

        $totalTransactions = count($fixtures['transactions']);
        $io->success(
            "Generated {$totalTransactions} transactions for {$merchantCount} merchants and stored in storage.",
        );

        return Command::SUCCESS;
    }
}
