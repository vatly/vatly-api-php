<?php

declare(strict_types=1);

namespace Vatly\API\Webhooks;

use Vatly\API\Types\WebhookEventName;

/**
 * The webhook endpoint verification ("setup") call.
 *
 * When a webhook endpoint is registered (or its URL changes), Vatly sends a
 * signed verification call. It is delivered as a **standard** webhook envelope
 * — `eventName` `webhook.setup`, `entityType` `webhook` — so it verifies and
 * parses exactly like any other event.
 *
 * {@see Webhook::parse()} returns this subclass for that event so receivers can
 * `instanceof`-detect it and acknowledge it (2xx) without running normal
 * event-specific handling. It is a {@see WebhookPayload} in every other respect.
 */
class WebhookSetupCallPayload extends WebhookPayload
{
    public const EVENT_NAME = WebhookEventName::WEBHOOK_SETUP;
}
