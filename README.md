# PayReckoner

PayReckoner is a fintech backend engine that processes streams of payment transactions, maintains accurate per-merchant ledgers in minor units, and applies configurable fee rules with priority-based matching. 

It ships a real-time fraud detection layer covering velocity abuse, amount spike anomalies, and round-trip patterns — all evaluated in a single O(n) pass. 

At end-of-day, it reconciles your internal ledger against bank settlement files, surfacing missing, extra, and mismatched transactions with per-currency disputed amount totals. 

The entire pipeline is designed around the constraints of real payment infrastructure: integer arithmetic throughout, net-of-fee precision at every stage, and clean separation between the ledger, fee, fraud, and reconciliation concerns.
