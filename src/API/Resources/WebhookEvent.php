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
     * @see \Vatly\API\Types\WebhookEventName
     * @example order.paid
     */
    public string $eventName;

    /**
     * Why the event fired, for events that have more than one cause.
     *
     * Always present; `null` for every event except `subscription.updated`,
     * where it is `updated_immediately`, `updated_on_renewal`, or `renewed`.
     * New values may be added over time.
     *
     * @example renewed
     */
    public ?string $reason = null;

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

    /**
     * @example 2023-08-11T10:48:51+02:00
     */
    public string $createdAt;

    public bool $testmode;

    public WebhookEventLinks $links;
}
