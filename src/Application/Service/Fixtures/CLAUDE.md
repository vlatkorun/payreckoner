# Fixtures Generation

This namespace contains three classes that together produce dummy data for testing.

## Classes

### `FixtureGenerator`

The top-level orchestrator. Generates merchant names internally, delegates transaction
and fee rule generation to the two specialised generators, and produces settlement
entries itself (since they are derived from the generated transactions).

**Method:** `generate(int $merchantCount, int $transactionsPerMerchant): array`

Returns:
```php
array{
    transactions: list<Transaction>,
    feeRules: list<FeeRule>,
    settlementEntries: list<array{txId: string, settledAmount: int, currency: string}>,
}
```

Settlement entries are built from CREDIT transactions only. Roughly 1-in-10 entries
carry a small random discrepancy (±500 minor units) to simulate reconciliation mismatches.
One extra entry (`TX-EXTRA-001`) is always appended, and one entry near the end is
removed, to guarantee both EXTRA and MISSING cases in reconciliation output.

**Dependencies (injected via constructor):**
- `TransactionFixtureGenerator`
- `FeeRuleFixtureGenerator`

---

### `TransactionFixtureGenerator`

Generates a flat list of `Transaction` DTOs for the given merchants.

**Method:** `generate(list<string> $merchants, int $perMerchant): list<Transaction>`

- IDs are sequential: `TX-0001`, `TX-0002`, …
- Every third transaction (by global counter) is `DEBIT`; the rest are `CREDIT`
- Amounts are random integers in minor units: `100–500000`
- Currency is chosen randomly from all `Currency` cases
- Timestamps start at `1700000000` and increment by 60 seconds per transaction

---

### `FeeRuleFixtureGenerator`

Generates a small fixed set of `FeeRule` DTOs.

**Method:** `generate(list<string> $merchants): list<FeeRule>`

Rules produced (in priority order):
1. First merchant + USD + minAmount 10000 → 150 bps
2. First merchant + any currency → 200 bps
3. Any merchant + EUR + maxAmount 100000 → 175 bps
4. Catch-all (no constraints) → 250 bps

Rules 1 and 2 are only added when at least one merchant exists.
