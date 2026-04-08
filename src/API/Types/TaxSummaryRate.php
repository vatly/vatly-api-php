<?php

namespace Vatly\API\Types;

class TaxSummaryRate
{
    public string $name;
    public float $percentage;
    public float $taxablePercentage;

    public function __construct(string $name, float $percentage, float $taxablePercentage)
    {
        $this->name = $name;
        $this->percentage = $percentage;
        $this->taxablePercentage = $taxablePercentage;
    }

    public static function createResourceFromApiResult($value): TaxSummaryRate
    {
        if (is_array($value)) {
            $value = (object) $value;
        }

        return new self(
            $value->name,
            $value->percentage,
            $value->taxablePercentage
        );
    }
}
