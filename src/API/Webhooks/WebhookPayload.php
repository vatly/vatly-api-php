<?php

declare(strict_types=1);

namespace Vatly\API\Webhooks;

/**
 * Represents a parsed and verified incoming webhook payload from Vatly.
 *
 * @see \Vatly\API\Resources\WebhookEvent
 */
class WebhookPayload
{
    /**
     * Unique webhook event ID.
     *
     * @example webhook_event_Qk8pRtSvWm2NjLhYcZaE
     */
    public string $id;

    /**
     * Resource type identifier.
     *
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
     * @var object|null
     */
    public ?object $object;

    /**
     * @example 2023-08-11T10:48:51+02:00
     */
    public string $createdAt;

    public bool $testmode;

    public function __construct(
        string $id,
        string $resource,
        string $eventName,
        string $entityType,
        string $entityId,
        bool $testmode,
        string $createdAt,
        ?object $object = null,
    ) {
        $this->id = $id;
        $this->resource = $resource;
        $this->eventName = $eventName;
        $this->entityType = $entityType;
        $this->entityId = $entityId;
        $this->object = $object;
        $this->testmode = $testmode;
        $this->createdAt = $createdAt;
    }
}
