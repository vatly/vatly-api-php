<?php

declare(strict_types=1);

namespace Vatly\API\Resources;

use Vatly\API\Resources\Links\WebhookEventLinks;

class WebhookEvent extends BaseResource
{
    /**
     * @example webhook_event_Qk8pRtSvWm2NjLhYcZaE
     */
    public string $id;

    /**
     * @example webhook_event
     */
    public string $resource;

    /**
     * Name of the event that triggered this webhook.
     *
     * @see \Vatly\API\Types\WebhookEvent
     * @example order.paid
     */
    public string $eventName;

    /**
     * Type of the resource this event relates to.
     *
     * @example order
     */
    public string $entityType;

    /**
     * ID of the resource this event relates to.
     *
     * @example order_Hn5xWqVfKm8RjTgYbUcP
     */
    public string $entityId;

    /**
     * The full resource payload at the time of the event.
     *
     * @var array|object|null
     */
    public $object = null;

    public WebhookEventLinks $links;
}
