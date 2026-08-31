<?php

declare(strict_types=1);

namespace Vatly\API\Webhooks\Events;

use Vatly\API\Resources\OneOffProduct as ApiOneOffProduct;
use Vatly\API\Types\WebhookEventName;

/**
 * Event representing an update to a one-off product being **submitted** for review at Vatly.
 * The change is held as a pending update (`updateStatus: pending`) and does not
 * affect live checkouts until it is approved.
 *
 * The signed webhook `object` is the one-off product — byte-identical to the
 * `GET /v1/one-off-products/{id}` body — so {@see \Vatly\API\Webhooks\WebhookEventFactory}
 * hydrates the api-php resource straight from the payload and builds the event
 * via {@see self::fromApiOneOffProduct()}, with no follow-up API call.
 *
 * @immutable
 */
class OneOffProductUpdateSubmitted
{
    public const VATLY_EVENT_NAME = WebhookEventName::ONE_OFF_PRODUCT_UPDATE_SUBMITTED;

    public function __construct(
        public string $oneOffProductId,
        public bool $testmode,
        public ApiOneOffProduct $oneOffProduct,
    ) {
        //
    }

    /**
     * Build from the api-php resource hydrated from the signed webhook payload.
     */
    public static function fromApiOneOffProduct(ApiOneOffProduct $oneOffProduct): self
    {
        return new self(
            oneOffProductId: $oneOffProduct->id,
            testmode: $oneOffProduct->testmode,
            oneOffProduct: $oneOffProduct,
        );
    }
}
