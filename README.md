# PayReckoner

PayReckoner is a fintech backend engine that processes streams of payment transactions, maintains accurate per-merchant ledgers in minor units, and applies configurable fee rules with priority-based matching. 

It ships a real-time fraud detection layer covering velocity abuse, amount spike anomalies, and round-trip patterns — all evaluated in a single O(n) pass. 

At end-of-day, it reconciles your internal ledger against bank settlement files, surfacing missing, extra, and mismatched transactions with per-currency disputed amount totals. 

The entire pipeline is designed around the constraints of real payment infrastructure: integer arithmetic throughout, net-of-fee precision at every stage, and clean separation between the ledger, fee, fraud, and reconciliation concerns.

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
