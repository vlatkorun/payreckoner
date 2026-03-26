<?php

declare(strict_types=1);

namespace PayReckoner\Reconciliation;

enum DiscrepancyType: string
{
    case MISSING = 'MISSING';
    case EXTRA = 'EXTRA';
    case AMOUNT_MISMATCH = 'AMOUNT_MISMATCH';
    case CURRENCY_MISMATCH = 'CURRENCY_MISMATCH';
}
