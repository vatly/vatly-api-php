<?php

declare(strict_types=1);

namespace Vatly\API\Webhooks\Events;

use Vatly\API\Resources\Order as ApiOrder;
use Vatly\API\Types\Money;
use Vatly\API\Types\OrderLineData;
use Vatly\API\Types\TaxSummaryCollection;
use Vatly\API\Types\WebhookEventName;
use Vatly\API\Webhooks\Concerns\NormalizesWebhookMetadata;

/**
 * Event representing a failed payment attempt against an order at Vatly —
 * typically the start of dunning.
 *
 * The webhook is order-scoped. To find the affected subscription, read
 * `lines[].relatedResources->subscriptionId`; it is null on orders from
 * before Vatly linked lines to subscriptions, so keep a fallback for those.
 *
 * Carries the full order shape (mirroring {@see OrderPaid}) so consumers can
 * surface failure details without a follow-up API call. The signed webhook
 * payload carries the same body as `GET /orders/{id}`, and
 * {@see \Vatly\API\Webhooks\WebhookEventFactory} hydrates this event straight
 * from it, with no follow-up API GET.
 *
 * @immutable
 */
class OrderPaymentFailed
{
    use NormalizesWebhookMetadata;

    public const VATLY_EVENT_NAME = WebhookEventName::ORDER_PAYMENT_FAILED;

    /**
     * @internal Build events with WebhookEventFactory or the static from*() constructors; this constructor may gain arguments in any position.
     */
    public function __construct(
        public string $customerId,
        public string $orderId,
        public string $status,
        public Money $total,
        public Money $subtotal,
        public TaxSummaryCollection $taxSummary,
        public ?string $invoiceNumber,
        public ?string $paymentMethod,
        public bool $testmode,
        /** @var array<string, mixed>|null */
        public ?array $metadata = null,
        /**
         * The order's lines, mapped the same way as on {@see OrderPaid}.
         * Empty when the order carries none.
         *
         * @var OrderLineData[]
         */
        public array $lines = [],
    ) {
        //
    }

    public static function fromApiOrder(ApiOrder $order): self
    {
        return new self(
            customerId: $order->customerId ?? '',
            orderId: $order->id,
            status: $order->status,
            total: $order->total,
            subtotal: $order->subtotal,
            taxSummary: $order->taxSummary,
            invoiceNumber: $order->invoiceNumber,
            paymentMethod: $order->paymentMethod,
            testmode: $order->testmode,
            metadata: self::normalizeMetadata($order->metadata),
            lines: self::mapLines($order),
        );
    }

    /**
     * Map the order's lines into {@see OrderLineData}. A payload without a
     * `lines` key yields no lines, as this event carried none before.
     *
     * @return OrderLineData[]
     */
    private static function mapLines(ApiOrder $order): array
    {
        if (! isset($order->lines)) {
            return [];
        }

        $lines = [];
        foreach ($order->lines() as $line) {
            $lines[] = OrderLineData::fromApiOrderLine($line);
        }

        return $lines;
    }
}
