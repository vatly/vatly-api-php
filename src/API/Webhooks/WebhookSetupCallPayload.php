<?php

declare(strict_types=1);

namespace Vatly\API\Webhooks;

/**
 * Represents the setup verification call sent when a webhook endpoint is registered.
 */
class WebhookSetupCallPayload extends WebhookPayload
{
    public const RESOURCE = 'webhook_setup_call';
    public const EVENT_NAME = 'webhook.setup';

    public string $message;

    public function __construct(string $message, bool $testmode)
    {
        parent::__construct(
            '',
            self::RESOURCE,
            self::EVENT_NAME,
            'webhook',
            '',
            $testmode,
            '',
            null,
        );

        $this->message = $message;
    }
}
