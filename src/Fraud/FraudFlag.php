<?php

declare(strict_types=1);

namespace PayReckoner\Fraud;

enum FraudFlag: string
{
    case RULE_A_VELOCITY = 'RULE_A_VELOCITY';
    case RULE_B_SPIKE = 'RULE_B_SPIKE';
    case RULE_C_ROUNDTRIP = 'RULE_C_ROUNDTRIP';
}
