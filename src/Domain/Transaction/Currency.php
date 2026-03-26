<?php

declare(strict_types=1);

namespace PayReckoner\Domain\Transaction;

enum Currency: string
{
    case USD = 'USD';
    case EUR = 'EUR';
    case GBP = 'GBP';
}
