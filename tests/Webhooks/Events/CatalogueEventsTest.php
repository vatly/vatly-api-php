<?php

declare(strict_types=1);

namespace Vatly\Tests\Webhooks\Events;

use Vatly\API\Resources\OneOffProduct;
use Vatly\API\Resources\SubscriptionPlan;
use Vatly\API\Types\Money;
use Vatly\API\Webhooks\Events\OneOffProductArchived;
use Vatly\API\Webhooks\Events\OneOffProductUnarchived;
use Vatly\API\Webhooks\Events\OneOffProductUpdateApproved;
use Vatly\API\Webhooks\Events\OneOffProductUpdateRejected;
use Vatly\API\Webhooks\Events\OneOffProductUpdateSubmitted;
use Vatly\API\Webhooks\Events\SubscriptionPlanArchived;
use Vatly\API\Webhooks\Events\SubscriptionPlanUnarchived;
use Vatly\API\Webhooks\Events\SubscriptionPlanUpdateApproved;
use Vatly\API\Webhooks\Events\SubscriptionPlanUpdateRejected;
use Vatly\API\Webhooks\Events\SubscriptionPlanUpdateSubmitted;
use Vatly\Tests\BaseTestCase;

class CatalogueEventsTest extends BaseTestCase
{
    private function makeApiOneOffProduct(): OneOffProduct
    {
        $product = new OneOffProduct($this->client);
        $product->id = 'one_off_product_123';
        $product->resource = 'one_off_product';
        $product->name = 'Premium License';
        $product->description = 'Lifetime access';
        $product->basePrice = Money::createResourceFromApiResult((object) ['value' => '299.00', 'currency' => 'EUR']);
        $product->taxBehavior = 'exclusive';
        $product->productType = 'saas';
        $product->testmode = false;
        $product->status = 'active';
        $product->archivedAt = null;

        return $product;
    }

    private function makeApiSubscriptionPlan(): SubscriptionPlan
    {
        $plan = new SubscriptionPlan($this->client);
        $plan->id = 'subscription_plan_123';
        $plan->resource = 'subscription_plan';
        $plan->name = 'Pro Monthly';
        $plan->description = 'Billed monthly';
        $plan->basePrice = Money::createResourceFromApiResult((object) ['value' => '29.00', 'currency' => 'EUR']);
        $plan->taxBehavior = 'exclusive';
        $plan->interval = 'month';
        $plan->intervalCount = 1;
        $plan->productType = 'saas';
        $plan->testmode = true;
        $plan->status = 'active';
        $plan->archivedAt = null;

        return $plan;
    }

    public function test_one_off_product_event_name_constants(): void
    {
        $this->assertSame('one_off_product.update_submitted', OneOffProductUpdateSubmitted::VATLY_EVENT_NAME);
        $this->assertSame('one_off_product.update_approved', OneOffProductUpdateApproved::VATLY_EVENT_NAME);
        $this->assertSame('one_off_product.update_rejected', OneOffProductUpdateRejected::VATLY_EVENT_NAME);
        $this->assertSame('one_off_product.archived', OneOffProductArchived::VATLY_EVENT_NAME);
        $this->assertSame('one_off_product.unarchived', OneOffProductUnarchived::VATLY_EVENT_NAME);
    }

    public function test_subscription_plan_event_name_constants(): void
    {
        $this->assertSame('subscription_plan.update_submitted', SubscriptionPlanUpdateSubmitted::VATLY_EVENT_NAME);
        $this->assertSame('subscription_plan.update_approved', SubscriptionPlanUpdateApproved::VATLY_EVENT_NAME);
        $this->assertSame('subscription_plan.update_rejected', SubscriptionPlanUpdateRejected::VATLY_EVENT_NAME);
        $this->assertSame('subscription_plan.archived', SubscriptionPlanArchived::VATLY_EVENT_NAME);
        $this->assertSame('subscription_plan.unarchived', SubscriptionPlanUnarchived::VATLY_EVENT_NAME);
    }

    public function test_one_off_product_events_build_from_the_api_resource(): void
    {
        $product = $this->makeApiOneOffProduct();

        foreach ([
            OneOffProductUpdateSubmitted::class,
            OneOffProductUpdateApproved::class,
            OneOffProductUpdateRejected::class,
            OneOffProductArchived::class,
            OneOffProductUnarchived::class,
        ] as $eventClass) {
            $event = $eventClass::fromApiOneOffProduct($product);

            $this->assertInstanceOf($eventClass, $event);
            $this->assertSame('one_off_product_123', $event->oneOffProductId);
            $this->assertFalse($event->testmode);
            $this->assertSame($product, $event->oneOffProduct);
            $this->assertSame('Premium License', $event->oneOffProduct->name);
        }
    }

    public function test_subscription_plan_events_build_from_the_api_resource(): void
    {
        $plan = $this->makeApiSubscriptionPlan();

        foreach ([
            SubscriptionPlanUpdateSubmitted::class,
            SubscriptionPlanUpdateApproved::class,
            SubscriptionPlanUpdateRejected::class,
            SubscriptionPlanArchived::class,
            SubscriptionPlanUnarchived::class,
        ] as $eventClass) {
            $event = $eventClass::fromApiSubscriptionPlan($plan);

            $this->assertInstanceOf($eventClass, $event);
            $this->assertSame('subscription_plan_123', $event->subscriptionPlanId);
            $this->assertTrue($event->testmode);
            $this->assertSame($plan, $event->subscriptionPlan);
            $this->assertSame('month', $event->subscriptionPlan->interval);
        }
    }
}
