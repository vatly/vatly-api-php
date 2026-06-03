<?php

declare(strict_types=1);

namespace Vatly\API\Webhooks\Events;

use Vatly\API\Data\OrderLineData;
use Vatly\API\Resources\Order as ApiOrder;
use Vatly\API\Types\TaxSummaryCollection;
use Vatly\API\Types\WebhookEventName;
use Vatly\API\Webhooks\Concerns\NormalizesWebhookMetadata;

/**
 * Event representing an order being paid at Vatly.
 *
 * Carries the full tax breakdown so consumers can materialize a local invoice
 * without a follow-up API call. The webhook payload itself is sparse; the
 * webhook event factory enriches via `GetOrder` before dispatching.
 *
 * @immutable
 */
class OrderPaid
{
    use NormalizesWebhookMetadata;

    public const VATLY_EVENT_NAME = WebhookEventName::ORDER_PAID;

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
        /**
         * The order's lines, mapped from the enriched API order. Empty for
         * back-compat when an order carries none.
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
            total: $order->total->toCents(),
            subtotal: $order->subtotal->toCents(),
            taxSummary: $order->taxSummary,
            currency: $order->total->currency,
            invoiceNumber: $order->invoiceNumber,
            paymentMethod: $order->paymentMethod,
            metadata: self::normalizeMetadata($order->metadata),
            lines: self::mapLines($order),
        );
    }

    /**
     * Map the enriched API order's lines (the `OrderLineCollection` returned
     * by `GetOrder`) into immutable {@see OrderLineData} DTOs.
     *
     * `productType`/`productId` are read straight off the API line and carried
     * as raw strings.
     *
     * @return OrderLineData[]
     */
    private static function mapLines(ApiOrder $order): array
    {
        $lines = [];

        foreach ($order->lines() as $line) {
            $lines[] = OrderLineData::fromApiOrderLine($line);
        }

        return $lines;
    }
}
