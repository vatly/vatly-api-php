<?php

declare(strict_types=1);

namespace Vatly\API\Exceptions;

/**
 * Thrown when a webhook is verified while no signing secret is configured.
 *
 * Extends {@see InvalidSignatureException}, so code that already catches
 * that exception keeps refusing the request. Catch this one first to answer
 * with a "not configured" response (such as HTTP 503) so Vatly retries the
 * delivery once the secret is set.
 */
class WebhookSecretNotConfiguredException extends InvalidSignatureException
{
    public function __construct(string $message = 'No webhook signing secret is configured. Set the secret from your Vatly dashboard to receive webhooks.')
    {
        parent::__construct($message);
    }
}
