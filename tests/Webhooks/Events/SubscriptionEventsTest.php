<?php

declare(strict_types=1);

namespace Vatly\Tests\Webhooks\Events;

use Vatly\API\Resources\Subscription;
use Vatly\API\Types\Mandate;
use Vatly\API\Webhooks\Events\SubscriptionBillingUpdated;
use Vatly\API\Webhooks\Events\SubscriptionCanceledImmediately;
use Vatly\API\Webhooks\Events\SubscriptionCanceledWithGracePeriod;
use Vatly\API\Webhooks\Events\SubscriptionCancellationGracePeriodCompleted;
use Vatly\API\Webhooks\Events\SubscriptionResumed;
use Vatly\API\Webhooks\Events\SubscriptionStarted;
use Vatly\API\Webhooks\Events\WebhookReceived;
use Vatly\Tests\BaseTestCase;

class SubscriptionEventsTest extends BaseTestCase
{
    private function makeApiSubscription(?Mandate $mandate = null): Subscription
    {
        $subscription = new Subscription($this->client);
        $subscription->id = 'sub_123';
        $subscription->customerId = 'cus_456';
        $subscription->subscriptionPlanId = 'plan_789';
        $subscription->name = 'Pro plan';
        $subscription->quantity = 2;
        $subscription->mandate = $mandate;

        return $subscription;
    }

    /**
     * @param array<string, mixed> $object
     */
    private function makeWebhook(string $eventName, array $object, string $createdAt = '2024-01-01T10:00:00+00:00'): WebhookReceived
    {
        return new WebhookReceived(
            id: 'webhook_event_1',
            resource: 'webhook_event',
            eventName: $eventName,
            entityType: 'subscription',
            entityId: 'sub_123',
            testmode: true,
            createdAt: $createdAt,
            object: $object,
        );
    }

    public function test_event_name_constants(): void
    {
        $this->assertSame('subscription.started', SubscriptionStarted::VATLY_EVENT_NAME);
        $this->assertSame('subscription.billing_updated', SubscriptionBillingUpdated::VATLY_EVENT_NAME);
        $this->assertSame('subscription.resumed', SubscriptionResumed::VATLY_EVENT_NAME);
        $this->assertSame('subscription.canceled_immediately', SubscriptionCanceledImmediately::VATLY_EVENT_NAME);
        $this->assertSame('subscription.canceled_with_grace_period', SubscriptionCanceledWithGracePeriod::VATLY_EVENT_NAME);
        $this->assertSame('subscription.cancellation_grace_period_completed', SubscriptionCancellationGracePeriodCompleted::VATLY_EVENT_NAME);
    }

    public function test_started_builds_from_api_subscription_with_mandate(): void
    {
        $event = SubscriptionStarted::fromApiSubscription($this->makeApiSubscription(new Mandate('card', '4242')));

        $this->assertSame('cus_456', $event->customerId);
        $this->assertSame('sub_123', $event->subscriptionId);
        $this->assertSame('plan_789', $event->planId);
        $this->assertSame('default', $event->type);
        $this->assertSame('Pro plan', $event->name);
        $this->assertSame(2, $event->quantity);
        $this->assertInstanceOf(Mandate::class, $event->mandate);
        $this->assertSame('card', $event->mandate->method);
        $this->assertSame('4242', $event->mandate->maskedIdentifier);
    }

    public function test_started_builds_from_api_subscription_without_mandate(): void
    {
        $event = SubscriptionStarted::fromApiSubscription($this->makeApiSubscription(null));

        $this->assertNull($event->mandate);
    }

    public function test_started_builds_from_webhook_with_embedded_mandate(): void
    {
        $event = SubscriptionStarted::fromWebhook($this->makeWebhook('subscription.started', [
            'customerId' => 'cus_456',
            'subscriptionPlanId' => 'plan_789',
            'name' => 'Pro plan',
            'quantity' => 3,
            'mandate' => ['method' => 'sepa_debit', 'maskedIdentifier' => 'NL91****4300'],
        ]));

        $this->assertSame('cus_456', $event->customerId);
        $this->assertSame('sub_123', $event->subscriptionId);
        $this->assertSame(3, $event->quantity);
        $this->assertInstanceOf(Mandate::class, $event->mandate);
        $this->assertSame('sepa_debit', $event->mandate->method);
        $this->assertSame('NL91****4300', $event->mandate->maskedIdentifier);
    }

    public function test_started_from_webhook_without_mandate_is_null(): void
    {
        $event = SubscriptionStarted::fromWebhook($this->makeWebhook('subscription.started', [
            'customerId' => 'cus_456',
            'subscriptionPlanId' => 'plan_789',
            'name' => 'Pro plan',
            'quantity' => 1,
        ]));

        $this->assertNull($event->mandate);
    }

    public function test_billing_updated_builds_from_api_subscription(): void
    {
        $event = SubscriptionBillingUpdated::fromApiSubscription($this->makeApiSubscription(new Mandate('card', '1881')));

        $this->assertSame('sub_123', $event->subscriptionId);
        $this->assertSame('plan_789', $event->planId);
        $this->assertSame('1881', $event->mandate->maskedIdentifier);
    }

    public function test_billing_updated_from_webhook_reads_embedded_mandate(): void
    {
        $event = SubscriptionBillingUpdated::fromWebhook($this->makeWebhook('subscription.billing_updated', [
            'customerId' => 'cus_456',
            'subscriptionPlanId' => 'plan_789',
            'name' => 'Pro plan',
            'quantity' => 1,
            'mandate' => ['method' => 'card', 'maskedIdentifier' => '4242'],
        ]));

        $this->assertSame('card', $event->mandate->method);
    }

    public function test_resumed_builds_from_webhook(): void
    {
        $event = SubscriptionResumed::fromWebhook($this->makeWebhook('subscription.resumed', [
            'customerId' => 'cus_456',
        ]));

        $this->assertSame('cus_456', $event->customerId);
        $this->assertSame('sub_123', $event->subscriptionId);
    }

    public function test_canceled_immediately_uses_created_at_as_ends_at(): void
    {
        $event = SubscriptionCanceledImmediately::fromWebhook($this->makeWebhook('subscription.canceled_immediately', [
            'customerId' => 'cus_456',
        ], '2024-03-15T08:30:00+00:00'));

        $this->assertSame('cus_456', $event->customerId);
        $this->assertSame('sub_123', $event->subscriptionId);
        $this->assertSame('2024-03-15T08:30:00+00:00', $event->endsAt->format('c'));
    }

    public function test_canceled_with_grace_period_uses_ended_at(): void
    {
        $event = SubscriptionCanceledWithGracePeriod::fromWebhook($this->makeWebhook('subscription.canceled_with_grace_period', [
            'customerId' => 'cus_456',
            'endedAt' => '2024-04-01T00:00:00+00:00',
        ]));

        $this->assertSame('2024-04-01T00:00:00+00:00', $event->endsAt->format('c'));
    }

    public function test_grace_period_completed_prefers_ended_at(): void
    {
        $event = SubscriptionCancellationGracePeriodCompleted::fromWebhook($this->makeWebhook('subscription.cancellation_grace_period_completed', [
            'customerId' => 'cus_456',
            'endedAt' => '2024-05-01T00:00:00+00:00',
        ]));

        $this->assertSame('2024-05-01T00:00:00+00:00', $event->endsAt->format('c'));
    }

    public function test_grace_period_completed_falls_back_to_created_at(): void
    {
        $event = SubscriptionCancellationGracePeriodCompleted::fromWebhook($this->makeWebhook('subscription.cancellation_grace_period_completed', [], '2024-05-02T12:00:00+00:00'));

        $this->assertSame('', $event->customerId);
        $this->assertSame('2024-05-02T12:00:00+00:00', $event->endsAt->format('c'));
    }
}
