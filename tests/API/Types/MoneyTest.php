<?php

declare(strict_types=1);

namespace Vatly\Tests\API\Types;

use PHPUnit\Framework\TestCase;
use Vatly\API\Types\Money;

class MoneyTest extends TestCase
{
    /**
     * @dataProvider centsProvider
     */
    public function test_it_converts_decimal_value_to_integer_cents(string $value, int $expected): void
    {
        $money = new Money('EUR', $value);

        $this->assertSame($expected, $money->toCents());
    }

    /**
     * @return array<string, array{0: string, 1: int}>
     */
    public function centsProvider(): array
    {
        return [
            'whole with two decimals' => ['100.00', 10000],
            'typical amount' => ['17.35', 1735],
            'sub-euro' => ['0.99', 99],
            'zero' => ['0.00', 0],
            'integer without decimals' => ['42', 4200],
            'single decimal digit' => ['5.5', 550],
            'extra decimal digits are truncated' => ['1.999', 199],
            'negative amount' => ['-12.50', -1250],
            'negative without decimals' => ['-7', -700],
            'large amount' => ['1000000.01', 100000001],
        ];
    }
}
