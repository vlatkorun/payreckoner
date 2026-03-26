# PayReckoner

PayReckoner is a fintech backend engine that processes streams of payment transactions, maintains accurate per-merchant ledgers in minor units, and applies configurable fee rules with priority-based matching. 

It ships a real-time fraud detection layer covering velocity abuse, amount spike anomalies, and round-trip patterns — all evaluated in a single O(n) pass. 

At end-of-day, it reconciles your internal ledger against bank settlement files, surfacing missing, extra, and mismatched transactions with per-currency disputed amount totals. 

The entire pipeline is designed around the constraints of real payment infrastructure: integer arithmetic throughout, net-of-fee precision at every stage, and clean separation between the ledger, fee, fraud, and reconciliation concerns.

## Project Overview

PayReckoner is a PHP 8.4+ fintech engine that processes payment transaction streams
through four sequential stages:

1. **Ledger** — net balance accumulation per merchant and currency
2. **Fee Engine** — priority-ordered fee rule matching on CREDIT transactions
3. **Fraud Engine** — real-time stateful detection (velocity, spike, round-trip)
4. **Reconciler** — bidirectional comparison of internal ledger vs bank settlement file

The engine is intentionally framework-light: a Symfony Console application with
no HTTP layer, no ORM, and no database. All state lives in memory per run.

## Application Structure

The project follows Domain-Driven Design with three layers under `src/`:

```
src/
├── Domain/                          # Pure domain logic — no framework dependencies
│   ├── Transaction/                 # Shared DTOs and enums (Transaction, TransactionType)
│   ├── Fee/                         # Fee rule engine (FeeEngine, FeeRule, FeeResult)
│   ├── Fraud/                       # Fraud detection (FraudEngine, FraudFlag, FraudResult)
│   ├── Ledger/                      # Balance accumulation (LedgerProcessor, LedgerEntry)
│   ├── Reconciliation/              # Settlement reconciliation (Reconciler, Discrepancy, etc.)
│   └── Pipeline/                    # Orchestrator (Pipeline, PipelineResult)
│
├── Application/                     # Use cases — bridges user intent to domain
│   └── Command/
│       ├── RunPipelineCommand.php   # Console command: reads JSON, runs Pipeline, formats output
│       └── GenerateFixturesCommand.php  # Console command: generates dummy data
│
└── Infrastructure/                  # Framework plumbing
    └── Console/
        └── Application.php          # Symfony Console bootstrap (registers commands)
```

**Layer rules:**
- `Domain/` has zero dependencies on `Application/` or `Infrastructure/`
- `Application/` depends on `Domain/` only
- `Infrastructure/` depends on `Application/` and `Domain/`

## Development

The project runs in Docker via Docker Compose. Two services are defined: a PHP 8.4 CLI
container (mounted at `/usr/src/app`) and a Redis instance.

Bring the environment up:

```bash
make up
```

Install Composer dependencies inside the PHP container:

```bash
make composer-install
```

Open an interactive shell inside the PHP container to run commands directly:

```bash
make shell
```

For the full list of available `make` commands, see [`CLAUDE.md`](./CLAUDE.md).
For Docker container details, see [`docs/DOCKER.md`](./docs/DOCKER.md).

## Testing

Run the full test suite:

```bash
make test
```

Run unit tests only (per-component: Ledger, Fee, Fraud, Reconciliation, Transaction):

```bash
make test-unit
```

Run integration tests only (full pipeline end-to-end):

```bash
make test-integration
```

## Code Quality

Run static analysis (PHPStan level 8):

```bash
make analyse
```

Run code style and formatting (PHP CS Fixer):

```bash
make fix
```
