<?php

namespace Vatly\API\Types;

class Money
{
    /**
     * @example "EUR"
     */
    public string $currency;

    /**
     * @example "100.00"
     */
    public string $value;

    public function __construct(string $currency, string $value)
    {
        $this->currency = $currency;
        $this->value = $value;
    }

    public static function createResourceFromApiResult($value): Money
    {
        if (is_array($value)) {
            $value = (object) $value;
        }

        return new self($value->currency, $value->value);
    }

    /**
     * Convert this Money's decimal-string value ("17.35") to integer cents (1735).
     *
     * Avoids the float-precision pitfalls of `(int) ((float) $value * 100)` by
     * splitting on the decimal point and computing the minor units with integer
     * math. Handles negative values and missing/short fractional parts.
     */
    public function toCents(): int
    {
        $value = $this->value;

        $negative = str_starts_with($value, '-');
        $value = ltrim($value, '-');

        if (str_contains($value, '.')) {
            [$major, $minor] = explode('.', $value, 2);
        } else {
            $major = $value;
            $minor = '0';
        }

        $minor = substr(str_pad($minor, 2, '0'), 0, 2);
        $cents = ((int) $major) * 100 + (int) $minor;

        return $negative ? -$cents : $cents;
    }
}
