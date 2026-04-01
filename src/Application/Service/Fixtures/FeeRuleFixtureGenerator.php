<?php

declare(strict_types=1);

namespace PayReckoner\Application\Service\Fixtures;

use PayReckoner\Domain\Fee\FeeRule;
use PayReckoner\Domain\Transaction\Currency;

readonly class FeeRuleFixtureGenerator
{
    /**
     * @param list<string> $merchants
     * @return list<FeeRule>
     */
    public function generate(array $merchants): array
    {
        $rules = [];
        $priority = 0;

        $firstMerchant = $merchants[0] ?? null;
        if ($firstMerchant !== null) {
            $rules[] = new FeeRule(
                priority: ++$priority,
                merchant: $firstMerchant,
                currency: Currency::USD,
                minAmount: 10000,
                maxAmount: null,
                feeBps: 150,
            );
            $rules[] = new FeeRule(
                priority: ++$priority,
                merchant: $firstMerchant,
                currency: null,
                minAmount: null,
                maxAmount: null,
                feeBps: 200,
            );
        }

        $rules[] = new FeeRule(
            priority: ++$priority,
            merchant: null,
            currency: Currency::EUR,
            minAmount: null,
            maxAmount: 100000,
            feeBps: 175,
        );

        $rules[] = new FeeRule(
            priority: ++$priority,
            merchant: null,
            currency: null,
            minAmount: null,
            maxAmount: null,
            feeBps: 250,
        );

        return $rules;
    }
}
