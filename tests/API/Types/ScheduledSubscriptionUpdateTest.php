<?php

declare(strict_types=1);

namespace Vatly\Tests\API\Types;

use Vatly\API\Types\Money;
use Vatly\API\Types\ScheduledSubscriptionUpdate;
use Vatly\Tests\BaseTestCase;

class ScheduledSubscriptionUpdateTest extends BaseTestCase
{
    /** @test */
    public function it_hydrates_all_fields_from_an_array(): void
    {
        $update = ScheduledSubscriptionUpdate::fromArray([
            'subscriptionPlanId' => 'subscription_plan_next',
            'name' => 'Pro Annual',
            'description' => 'Billed yearly',
            'basePrice' => ['value' => '990.00', 'currency' => 'EUR'],
            'quantity' => 2,
            'interval' => 'year',
            'intervalCount' => 1,
            'effectiveAt' => '2024-03-15T10:30:00Z',
        ]);

        $this->assertSame('subscription_plan_next', $update->subscriptionPlanId);
        $this->assertSame('Pro Annual', $update->name);
        $this->assertSame('Billed yearly', $update->description);
        $this->assertInstanceOf(Money::class, $update->basePrice);
        $this->assertSame('990.00', $update->basePrice->value);
        $this->assertSame(2, $update->quantity);
        $this->assertSame('year', $update->interval);
        $this->assertSame(1, $update->intervalCount);
        $this->assertSame('2024-03-15T10:30:00Z', $update->effectiveAt);
    }

    /** @test */
    public function it_defaults_effective_at_to_null_when_missing(): void
    {
        $update = ScheduledSubscriptionUpdate::fromArray([
            'subscriptionPlanId' => 'subscription_plan_next',
            'name' => 'Pro Annual',
            'description' => 'Billed yearly',
            'basePrice' => ['value' => '990.00', 'currency' => 'EUR'],
            'quantity' => 2,
            'interval' => 'year',
            'intervalCount' => 1,
        ]);

        $this->assertNull($update->effectiveAt);
    }

    /** @test */
    public function it_hydrates_from_a_stdclass_api_result(): void
    {
        $value = json_decode((string) json_encode([
            'subscriptionPlanId' => 'subscription_plan_next',
            'name' => 'Pro Annual',
            'description' => 'Billed yearly',
            'basePrice' => ['value' => '990.00', 'currency' => 'EUR'],
            'quantity' => 3,
            'interval' => 'month',
            'intervalCount' => 6,
            'effectiveAt' => null,
        ]));

        $update = ScheduledSubscriptionUpdate::createResourceFromApiResult($value);

        $this->assertSame('subscription_plan_next', $update->subscriptionPlanId);
        $this->assertSame('990.00', $update->basePrice->value);
        $this->assertSame(3, $update->quantity);
        $this->assertSame('month', $update->interval);
        $this->assertSame(6, $update->intervalCount);
        $this->assertNull($update->effectiveAt);
    }
}
