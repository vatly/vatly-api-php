<?php

declare(strict_types=1);

namespace Vatly\Tests\API\Types;

use ReflectionClass;
use Vatly\API\Types\WebhookEventName;
use Vatly\API\Types\WebhookSubscriptionEventName;
use Vatly\Tests\BaseTestCase;

class WebhookSubscriptionEventNameTest extends BaseTestCase
{
    /**
     * The subscribable public event names, exactly as declared by the spec's
     * `WebhookSubscriptionEventName` enum.
     *
     * @var string[]
     */
    private const SPEC_ENUM = [
        'checkout.canceled',
        'checkout.expired',
        'checkout.failed',
        'checkout.paid',
        'one_off_product.archived',
        'one_off_product.unarchived',
        'one_off_product.update_approved',
        'one_off_product.update_rejected',
        'one_off_product.update_submitted',
        'order.canceled',
        'order.chargeback_received',
        'order.chargeback_reversed',
        'order.paid',
        'order.payment_failed',
        'refund.canceled',
        'refund.completed',
        'refund.failed',
        'subscription.billing_updated',
        'subscription.canceled_for_nonpayment',
        'subscription.canceled_immediately',
        'subscription.canceled_with_grace_period',
        'subscription.cancellation_grace_period_completed',
        'subscription.resumed',
        'subscription.started',
        'subscription.update_scheduled',
        'subscription.updated',
        'subscription_plan.archived',
        'subscription_plan.unarchived',
        'subscription_plan.update_approved',
        'subscription_plan.update_rejected',
        'subscription_plan.update_submitted',
    ];

    /** @test */
    public function its_constants_match_the_spec_enum_exactly(): void
    {
        $constants = array_values((new ReflectionClass(WebhookSubscriptionEventName::class))->getConstants());

        sort($constants);
        $expected = self::SPEC_ENUM;
        sort($expected);

        $this->assertSame($expected, $constants);
        $this->assertCount(31, $constants);
    }

    /** @test */
    public function it_excludes_the_non_subscribable_webhook_setup_event(): void
    {
        $constants = array_values((new ReflectionClass(WebhookSubscriptionEventName::class))->getConstants());

        $this->assertNotContains(WebhookEventName::WEBHOOK_SETUP, $constants);
        $this->assertNotContains('webhook.setup', $constants);
    }

    /** @test */
    public function each_subscribable_name_is_also_a_known_webhook_event_name(): void
    {
        $eventNames = array_values((new ReflectionClass(WebhookEventName::class))->getConstants());

        foreach ((new ReflectionClass(WebhookSubscriptionEventName::class))->getConstants() as $value) {
            $this->assertContains($value, $eventNames, "$value should be a known WebhookEventName");
        }
    }
}
