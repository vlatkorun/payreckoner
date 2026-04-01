# PayReckoner

## Project Overview

PayReckoner is a PHP 8.4+ fintech engine that processes payment transaction streams
through four sequential stages:

1. **Ledger** — net balance accumulation per merchant and currency
2. **Fee Engine** — priority-ordered fee rule matching on CREDIT transactions
3. **Fraud Engine** — real-time stateful detection (velocity, spike, round-trip)
4. **Reconciler** — bidirectional comparison of internal ledger vs bank settlement file

For full functional specification of all four parts, see [`REQUIREMENTS.md`](./docs/REQUIREMENTS.md).

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
    ├── Config/                      # Configuration loading (ConfigurationLoader, definitions)
    ├── Storage/
│   │   └── Redis/                   # Redis connection and record storage (RedisConnectionFactory, RedisRecordStorage)
    └── Console/
        └── Application.php          # Symfony Console bootstrap — builds DI container, registers commands
```

Service wiring uses `symfony/dependency-injection`. All service definitions live in
`config/services.yaml` and are loaded at boot time via `YamlFileLoader`. Redis connection
parameters are loaded from `config/redis.yaml`, which resolves the `REDIS_HOST`, `REDIS_PORT`,
and `REDIS_DB` environment variables into DI parameters.

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

## Fixtures

Before running the pipeline, populate the data store with dummy data using the
`generate:fixtures` command. Commands like `run:pipeline` depend on this data
being present.

```bash
docker compose exec php bin/payreckoner generate:fixtures <merchants> [--count=<n>]
```

- `<merchants>` — number of merchants to generate (required)
- `--count` / `-c` — number of transactions per merchant (default: `10`)

Example — 5 merchants with 20 transactions each:

```bash
docker compose exec php bin/payreckoner generate:fixtures 5 --count=20
```

This stores three sets of records:

| Record set | Contents |
|------------|----------|
| `transactions` | Generated `Transaction` records |
| `fee_rules` | Generated `FeeRule` records |
| `settlement_entries` | Settlement entries derived from CREDIT transactions |

No fixture files are checked into the repository. Re-run the command any time
you want a fresh data set.

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
