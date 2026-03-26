# Application Commands

## GenerateFixturesCommand (`generate:fixtures`)

Generates dummy transaction, fee rule, and settlement records for testing.

**Usage:** `bin/payreckoner generate:fixtures <merchants> [--count=10]`

- `merchants` — required argument, number of merchants to create
- `--count` / `-c` — transactions per merchant (default: 10)

**Dependencies (injected via constructor):**

- `FixtureGenerator` (`Application\Service`) — generates transactions, fee rules,
  and settlement entries for the given merchant count and transactions-per-merchant
- `RecordStorageInterface` (`Application\Port`) — stores each record set by key;
  current implementation is `RedisRecordStorage` (`Infrastructure\Storage`)

**Redis keys written:**

- `fixtures:transactions`
- `fixtures:fee_rules`
- `fixtures:settlement_entries`

The command contains no generation or storage logic — it delegates to `FixtureGenerator`
for data creation and `RecordStorageInterface` for persistence.
