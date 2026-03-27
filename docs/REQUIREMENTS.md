# PayReckoner — Requirements

## Overview

PayReckoner processes streams of payment transactions through four sequential stages:
ledger accumulation, fee processing, fraud detection, and settlement reconciliation.
All monetary amounts are in **minor units** (e.g. 1000 = $10.00 USD).

---

## Transaction Schema

Every transaction has the following fields:

| Field       | Type     | Description                                |
|-------------|----------|--------------------------------------------|
| `id`        | `string` | Unique transaction identifier              |
| `type`      | `string` | `CREDIT` or `DEBIT`                        |
| `amount`    | `int`    | Minor units, always positive               |
| `currency`  | `string` | ISO 4217 code (e.g. `USD`, `EUR`)          |
| `merchant`  | `string` | Lowercase alphanumeric merchant identifier |
| `timestamp` | `int`    | Unix timestamp in seconds                  |

**Constraints:** No duplicate IDs. Up to 100,000 transactions per run.

---

## Part 1 — Ledger

Calculate the net balance per merchant per currency across all transactions.

- `CREDIT` adds to the balance
- `DEBIT` subtracts from the balance
- Zero and negative balances are valid and must appear in output
- Output sorted by merchant ascending, then currency ascending

**Output per entry:** `merchant`, `currency`, `balance`

---

## Part 2 — Fee Engine

Apply processing fees to `CREDIT` transactions only using a configurable rule set.

### Fee Rule Schema

| Field | Type | Description |
|---|---|---|
| `priority` | `int` | Lower number = higher priority |
| `merchant` | `string\|null` | `null` matches any merchant |
| `currency` | `string\|null` | `null` matches any currency |
| `min_amount` | `int\|null` | `null` = no lower bound |
| `max_amount` | `int\|null` | `null` = no upper bound |
| `fee_bps` | `int` | Fee in basis points (1 bps = 0.01%) |

### Matching Rules

- Rules are not guaranteed to arrive pre-sorted — sort by `priority` ascending before processing
- The **first matching rule wins** — stop evaluating after the first match
- A rule matches only if **all non-null fields** match the transaction
- If no rule matches, fee = 0

### Fee Calculation

```
fee = floor(amount × fee_bps / 10000)
```

- Always multiply before dividing
- Always use `floor()` — never `round()`
- Result is always an integer
- The net credited amount = `amount - fee`
- Ledger balances use the net-of-fee amount for CREDIT transactions
- Up to 50 fee rules per run

**Output per transaction:** `tx_id`, `fee`, `net_amount`, `matched_priority`

---

## Part 3 — Fraud Detection

Evaluate each transaction in timestamp order against three fraud rules.
Flagged transactions are still processed — fraud detection is non-blocking.
A transaction may be flagged by multiple rules simultaneously.

**Pre-condition:** sort transactions by timestamp ascending before processing.
For equal timestamps, preserve original input order.

### Rule A — Velocity Check

Flag a `CREDIT` if the merchant has more than **N** credits within any rolling
60-second window (strictly greater than N).

- N is a runtime parameter
- Window is time-based: evict timestamps older than `current_timestamp - 60`
- Flag code: `RULE_A_VELOCITY`

### Rule B — Amount Spike

Flag a `CREDIT` if its net-of-fee amount exceeds 3× the merchant's rolling average
of all **prior** credit amounts.

- Skip evaluation if the merchant has fewer than 3 prior credits
- The current transaction is excluded from the average used to evaluate it
- Update the running average **after** evaluation
- Flag code: `RULE_B_SPIKE`

### Rule C — Round-Trip Detection

Flag a `DEBIT` if it matches a prior `CREDIT` of the exact same amount for the
same merchant within a 1-second window.

- Match is against the **net-of-fee** credit amount, not the original amount
- "Within 1 second" means `|debit_timestamp - credit_timestamp| ≤ 1`
- Flag code: `RULE_C_ROUNDTRIP`

**Output per transaction:** `tx_id`, `type`, `merchant`, `timestamp`, `flags[]`

---

## Part 4 — Reconciliation

Compare the internal ledger (CREDIT transactions only) against a bank settlement
file and produce a structured discrepancy report.

### Settlement File Schema

| Field | Type | Description |
|---|---|---|
| `tx_id` | `string` | Transaction identifier |
| `settled_amount` | `int` | Amount the bank settled, in minor units |
| `currency` | `string` | ISO 4217 currency code |

### Discrepancy Types

| Code | Condition |
|---|---|
| `MISSING` | Transaction in your ledger but absent from the settlement file |
| `EXTRA` | Transaction in the settlement file but unknown to your ledger |
| `AMOUNT_MISMATCH` | Present in both, currencies match, but amounts differ |
| `CURRENCY_MISMATCH` | Present in both, but currency codes differ |

### Matching Logic

- Walk the ledger against the settlement file (catches `MISSING` and mismatches)
- Walk the settlement file against the ledger (catches `EXTRA`)
- Check currency before amount — `CURRENCY_MISMATCH` takes priority over `AMOUNT_MISMATCH`
- For `CURRENCY_MISMATCH`, the difference field is `null` (amounts across currencies
  are not comparable)
- For `AMOUNT_MISMATCH`, difference = `|settled_amount - net_amount|`

### Summary

The report includes a summary with:

- Total ledger CREDIT count
- Total settlement file entry count
- Matched count (no discrepancy)
- Total discrepancy count
- Discrepancy breakdown by type
- Disputed amounts grouped by currency (sum of absolute differences per currency)

**Output:** `discrepancies[]` + `summary`

---

## General Constraints

| Concern | Requirement |
|---|---|
| Monetary arithmetic | Integer (minor units) throughout — no floats |
| Fee formula | `floor(amount × fee_bps / 10000)` always |
| Cross-currency summation | Never — disputed amounts are always per-currency |
| Unknown transaction type | Throw — do not silently ignore |
| Duplicate transaction ID | Throw — treat as corrupt input |
| Negative ledger balance | Valid — do not suppress |
| No matching fee rule | Fee = 0, full amount credited |
| Transaction ordering | Sort by timestamp before fraud evaluation; preserve input order for ties |
| Pass count | Each stage processes transactions in a single forward pass |