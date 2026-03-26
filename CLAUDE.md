# PayReckoner — CLAUDE.md (Root)

This file provides general project guidelines for Claude Code when working with code in this repository..
Each component under `src/` has its own `CLAUDE.md` with domain-specific rules.
When working inside a subdirectory, read both this file and the local one.

---

## Project Overview

PayReckoner is a PHP 8.2+ fintech engine that processes payment transaction streams
through four sequential stages:

1. **Ledger** — net balance accumulation per merchant and currency
2. **Fee Engine** — priority-ordered fee rule matching on CREDIT transactions
3. **Fraud Engine** — real-time stateful detection (velocity, spike, round-trip)
4. **Reconciler** — bidirectional comparison of internal ledger vs bank settlement file

For full functional specification of all four parts, see [`REQUIREMENTS.md`](./docs/REQUIREMENTS.md).

The engine is intentionally framework-light: a Symfony Console application with
no HTTP layer, no ORM, and no database. All state lives in memory per run.

---

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

Supporting files at the project root:

```
bin/payreckoner              # Console entrypoint — boots Infrastructure\Console\Application
tests/                       # Mirrors src/ structure exactly (Domain/, Application/)
composer.json
phpunit.xml
phpstan.neon
.php-cs-fixer.php
```

**Layer rules:**
- `Domain/` has zero dependencies on `Application/` or `Infrastructure/`
- `Application/` depends on `Domain/` only
- `Infrastructure/` depends on `Application/` and `Domain/`
- `bin/payreckoner` depends on `Infrastructure/` only

Each `src/Domain/` subdirectory has its own `CLAUDE.md` describing internal structure,
rules, and implementation constraints specific to that component.

---

## Data Flow

```
Input JSON (transactions + fee rules + settlement file)
    │
    ▼
Transaction[]  ──►  Pipeline::run()
                        │
                        ├─ 1. Sort by timestamp (stable, preserve input order on ties)
                        ├─ 2. FeeEngine::process()      → FeeResult[] (per CREDIT tx)
                        ├─ 3. LedgerProcessor::build()  → LedgerEntry[] (net balances)
                        ├─ 4. FraudEngine::evaluate()   → FraudFlag[] (per tx)
                        └─ 5. Reconciler::reconcile()   → ReconciliationReport
```

`Pipeline` is the single public entry point. Downstream stages receive only what
they need — no stage reaches back into a previous stage's internal state.

---

## Monetary Precision — Non-Negotiable Rules

These rules apply everywhere in the codebase without exception:

- **All amounts are in minor units (integers).** 1000 = $10.00 USD. Never convert
  to decimals for arithmetic.
- **Never use `float` for monetary values.** This includes intermediate calculations.
  The only exception is computing averages for fraud thresholds (Rule B), which are
  comparison-only and never stored or output.
- **Fee formula:** `(int) floor($amount * $feeBps / 10000)` — always multiply before
  dividing, always `floor()` (not `round()`), always cast back to `int`.
- **Never sum amounts across currencies.** Disputed amounts in the reconciliation
  report are always grouped by currency code.
- Use `brick/money` when representing monetary values as objects. Raw `int` is
  acceptable in internal engine logic where currency is tracked separately.

---

## PHP Standards

**Minimum version:** PHP 8.4

**Always use:**
- Property hooks for computed properties on DTOs where applicable (PHP 8.4 native)
- `readonly class` for all DTOs (Transaction, FeeRule, FeeResult, LedgerEntry,
  Discrepancy) — immutable by construction
- `enum` for all finite value sets: `TransactionType`, `FraudFlag`, `DiscrepancyType`
- Named arguments when constructing DTOs with more than three parameters
- `match` expression instead of `switch` in classifiers and rule matchers —
  exhaustive by default, throws `UnhandledMatchError` on unexpected input
- Union types and `?Type` nullability explicitly — never rely on implicit null
- `strict_types=1` in every file

**Never use:**
- `array_shift()` on large arrays — use `SplDoublyLinkedList::shift()` (O(1))
- Mutable static state or global variables
- `floatval()` / `(float)` on monetary amounts
- String concatenation to build composite lookup keys (use nested arrays)
- `@` error suppression

---

## Architecture Constraints

- **Single responsibility per class.** `FeeEngine` matches rules and calculates fees.
  It does not touch the ledger. `LedgerProcessor` accumulates balances. It does not
  apply fees — it receives net amounts already computed by `FeeEngine`.
- **No stage modifies another stage's output.** Each stage produces a new data
  structure; it never mutates the previous stage's result.
- **One forward pass per stage.** No stage may loop over transactions more than once.
  Pre-sort once in `Pipeline`, pass the sorted array to all stages.
- **No hidden O(n²).** Any loop inside a loop must be justified with a comment
  explaining why it is bounded (e.g. "r ≤ 50 fee rules").
- **Separation of state.** Each stateful component (FraudEngine) owns its state
  internally. State is never passed in from outside and never leaks out.

---

## Error Handling

- **Unknown transaction type:** throw `\InvalidArgumentException` with the tx ID.
  Do not silently skip — unknown types in a ledger are data integrity issues.
- **Duplicate transaction ID:** throw `\RuntimeException`. The spec forbids duplicates;
  if they appear, the input is corrupt.
- **No matching fee rule:** fee = 0, full amount credited. Do not throw.
- **Negative ledger balance:** valid, do not suppress. A merchant can have more
  debits than credits.
- **CURRENCY_MISMATCH in reconciliation:** do not attempt to compute a difference.
  Set `difference` to `null`. Comparing amounts across currencies is undefined.

---

## Docker

The project runs in Docker via `docker-compose.yml`. Two services are defined:

- **php** — `thecodingmachine/php:8.4-v5-slim-cli`, mounted at `/usr/src/app`
  with the Redis and Xdebug extensions enabled
- **redis** — `redis:7-alpine`, exposed on port `6379`

Extensions are enabled via environment variables (`PHP_EXTENSION_REDIS=1`,
`PHP_EXTENSION_XDEBUG=1`). Xdebug's `client_host` is auto-configured for macOS
via `host.docker.internal`.

### Make commands

Use the `Makefile` at the project root to manage the environment:

```bash
make up                # Start all containers in detached mode
make down              # Stop and remove containers
make restart           # Full stop + start cycle
make logs              # Tail all container logs
make ps                # Show container status
make composer-install  # Install Composer dependencies inside the php container
make shell             # Open an interactive bash shell inside the php container
make test              # Run the full test suite
make test-unit         # Run unit tests only (Ledger, Fee, Fraud, Reconciliation, Transaction)
make test-integration  # Run integration tests only (full pipeline end-to-end)
```

For full functional specification of the Docker containers, see [`DOCKER.md`](./docs/DOCKER.md).

---

## Testing

- **Test file location:** mirrors `src/` exactly. `src/Domain/Fee/FeeEngine.php` →
  `tests/Domain/Fee/FeeEngineTest.php`
- **Each part has its own test suite.** Do not write cross-component tests in a
  component's own test file — cross-component behaviour belongs in
  `tests/Domain/Pipeline/PipelineTest.php`
- **Always test edge cases explicitly:** empty input, zero balance, no matching fee
  rule, fewer than 3 prior credits (Rule B skip), negative balance, EXTRA and MISSING
  in both reconciliation directions
- **Use data providers** for fee rule matching — there are many rule/transaction
  combinations to cover exhaustively
- **Monetary assertions:** always assert the exact integer value. Never use
  `assertEqualsWithDelta` on monetary output

Run tests via Make (executes inside the Docker container):
```bash
make test              # full test suite
make test-unit         # unit tests only (Ledger, Fee, Fraud, Reconciliation, Transaction)
make test-integration  # integration tests only (full pipeline end-to-end)
```

## Code Quality

Run static analysis via Make (executes inside the Docker container):
```bash
make analyse # alias for vendor/bin/phpstan analyse --level=8
```

Run code style and formatting via Make (executes inside the Docker container):
```bash
make fix # alias for vendor/bin/php-cs-fixer fix
```

---

## Tooling Configuration

**PHPStan:** level 8 minimum. No baseline file — all errors must be fixed, not
suppressed. Pay particular attention to nullability in fee rule fields (`?string`,
`?int`) — PHPStan will catch wildcard-handling bugs that unit tests might miss.

**PHP CS Fixer:** PSR-12 base, with:
- `declare(strict_types=1)` enforced on all files
- `ordered_imports` alphabetically
- No unused imports

**PHPUnit:** version 11+. Use constructor property promotion and PHP 8.4 property hooks in test classes where appropriate.
Avoid `setUp()` where a data provider suffices.

---

## Git Commits

Before committing changes, **ALWAYS** use the `/commit` skill. It enforces conventional commit format, ensures the message explains both what and why, and forbids generic messages or Claude attributions. No exceptions — every `git commit` must go through this skill.

---

## Fixtures

Dummy transaction data is generated on-the-fly via the `GenerateFixturesCommand`
console command (`bin/payreckoner generate:fixtures`). No fixture files are
checked into the repository. Integration test fixtures are defined inline in
`tests/Domain/Pipeline/PipelineTest.php`.

---