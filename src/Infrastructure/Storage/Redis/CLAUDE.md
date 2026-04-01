# Redis Storage — `Infrastructure\Storage\Redis`

This namespace contains the Redis implementation of `Application\Port\RecordStorageInterface`.
It is the only concrete storage backend in the project and is used exclusively by `GenerateFixturesCommand`
to persist generated fixture data.

---

## Classes

### `RedisConnectionFactory`

Creates and configures a `\Redis` instance (php-redis extension).

- Receives `$host`, `$port`, and `$database` as constructor arguments (injected by DI).
- `create()` opens a new connection, selects the target database, and returns the `\Redis` object.
- Does not pool or cache connections — each `create()` call opens a fresh connection.

### `RedisRecordStorage`

Implements `RecordStorageInterface`. Wraps `RedisConnectionFactory` and owns the connection lifecycle.

- The `\Redis` connection is lazy: instantiated on first use via `$this->redis ??= $this->connectionFactory->create()`.
- `store(string $key, array $records)` — generic method; JSON-encodes the array and writes it with `SET`.
  Uses `JSON_THROW_ON_ERROR` — malformed data throws `\JsonException`, not silently corrupts.
- `storeTransactions(array $transactions)` — serialises `Transaction` domain objects to plain arrays
  and delegates to `store('fixtures:transactions', ...)`.
- `storeFeeRules(array $feeRules)` — serialises `FeeRule` domain objects (including nullable fields)
  and delegates to `store('fixtures:fee_rules', ...)`.

**Redis keys written:**

| Key                    | Written by           | Content                        |
|------------------------|----------------------|--------------------------------|
| `fixtures:transactions`| `storeTransactions`  | JSON array of transaction maps |
| `fixtures:fee_rules`   | `storeFeeRules`      | JSON array of fee rule maps    |

No TTL is set — keys persist until explicitly deleted or Redis is flushed.

---

## Configuration

Connection parameters flow from environment variables through the Symfony DI parameter system:

```
REDIS_HOST  (default: localhost)  →  %redis.host%    →  RedisConnectionFactory::$host
REDIS_PORT  (default: 6379)       →  %redis.port%    →  RedisConnectionFactory::$port
REDIS_DB    (default: 0)          →  %redis.database% → RedisConnectionFactory::$database
```

**`config/redis.yaml`** — declares the DI parameters, resolving env vars with type coercion:

```yaml
parameters:
    env(REDIS_HOST): 'localhost'
    env(REDIS_PORT): '6379'
    env(REDIS_DB):   '0'

    redis.host:     '%env(string:REDIS_HOST)%'
    redis.port:     '%env(int:REDIS_PORT)%'
    redis.database: '%env(int:REDIS_DB)%'
```

**`Infrastructure\Config\Definition\RedisConfiguration`** — Symfony Config component tree that validates
the resolved values (host non-empty, port 1–65535, database 0–15). It is separate from the DI parameter
loading and is used by `ConfigurationLoader` when config is loaded via the PHP-based config path.

---

## DI Wiring (`config/services.yaml`)

```yaml
PayReckoner\Infrastructure\Storage\Redis\RedisConnectionFactory:
    arguments:
        $host:     '%redis.host%'
        $port:     '%redis.port%'
        $database: '%redis.database%'

PayReckoner\Infrastructure\Storage\Redis\RedisRecordStorage:
    arguments:
        - '@PayReckoner\Infrastructure\Storage\Redis\RedisConnectionFactory'
```

`RedisRecordStorage` is injected into `GenerateFixturesCommand` as a `RecordStorageInterface`:

```yaml
PayReckoner\Application\Command\GenerateFixturesCommand:
    arguments:
        - '@PayReckoner\Application\Service\Fixtures\FixtureGenerator'
        - '@PayReckoner\Infrastructure\Storage\Redis\RedisRecordStorage'
```

---

## Bootstrap path

`Infrastructure\Console\Application` (the Symfony Console entry point) builds the DI container:

1. Creates a `ContainerBuilder` and sets `%config_path%` to the absolute `config/` directory.
2. Loads `config/redis.yaml` — registers the `%redis.*%` parameters.
3. Loads `config/services.yaml` — registers all services including `RedisConnectionFactory` and `RedisRecordStorage`.
4. Compiles the container and pulls out command instances.

No service is instantiated manually — everything goes through the compiled container.

---

## Interface contract

`RecordStorageInterface` (`Application\Port`) defines three methods:

- `store(string $key, array $records): void` — raw keyed write
- `storeTransactions(array $transactions): void`
- `storeFeeRules(array $feeRules): void`

`RedisRecordStorage` is the sole implementation. If a second storage backend is needed, implement
the interface and rewire the DI entry for `GenerateFixturesCommand`.
