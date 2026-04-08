<?php

namespace Vatly\API\Types;

class TaxSummaryItem
{
    public TaxSummaryRate $taxRate;
    public Money $amount;

    public function __construct(TaxSummaryRate $taxRate, Money $amount)
    {
        $this->taxRate = $taxRate;
        $this->amount = $amount;
    }

    public static function createResourceFromApiResult($value): TaxSummaryItem
    {
        if (is_array($value)) {
            $value = (object) $value;
        }

        return new self(
            TaxSummaryRate::createResourceFromApiResult($value->taxRate),
            Money::createResourceFromApiResult($value->amount)
        );
    }
}
