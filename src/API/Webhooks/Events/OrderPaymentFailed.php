<?php

declare(strict_types=1);

namespace Vatly\API\Webhooks\Events;

use Vatly\API\Resources\Order as ApiOrder;
use Vatly\API\Types\TaxSummaryCollection;
use Vatly\API\Types\WebhookEventName;
use Vatly\API\Webhooks\Concerns\NormalizesWebhookMetadata;

/**
 * Event representing a failed payment attempt against an order at Vatly —
 * typically the start of dunning.
 *
 * The webhook is order-scoped; consumers that need to identify the affected
 * subscription should resolve it via their own customer/subscription map
 * (the same pattern as for renewal `order.paid`).
 *
 * Carries the full order shape (mirroring {@see OrderPaid}) so consumers can
 * surface failure details without a follow-up API call. The webhook payload
 * itself is sparse; the webhook event factory enriches via `GetOrder`.
 *
 * @immutable
 */
class OrderPaymentFailed
{
    use NormalizesWebhookMetadata;

    public const VATLY_EVENT_NAME = WebhookEventName::ORDER_PAYMENT_FAILED;

    public function __construct(
        public string $customerId,
        public string $orderId,
        public string $status,
        public int $total,
        public int $subtotal,
        public TaxSummaryCollection $taxSummary,
        public string $currency,
        public ?string $invoiceNumber,
        public ?string $paymentMethod,
        /** @var array<string, mixed>|null */
        public ?array $metadata = null,
    ) {
        //
    }

    public static function fromApiOrder(ApiOrder $order): self
    {
        return new self(
            customerId: $order->customerId ?? '',
            orderId: $order->id,
            status: $order->status,
            total: $order->total->toCents(),
            subtotal: $order->subtotal->toCents(),
            taxSummary: $order->taxSummary,
            currency: $order->total->currency,
            invoiceNumber: $order->invoiceNumber,
            paymentMethod: $order->paymentMethod,
            metadata: self::normalizeMetadata($order->metadata),
        );
    }
}
