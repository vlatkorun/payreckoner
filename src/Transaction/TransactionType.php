<?php

declare(strict_types=1);

namespace PayReckoner\Transaction;

enum TransactionType: string
{
    case CREDIT = 'CREDIT';
    case DEBIT = 'DEBIT';
}
