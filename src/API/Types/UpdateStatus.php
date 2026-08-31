<?php

namespace Vatly\API\Types;

/**
 * Lifecycle of a pending catalogue update (one-off product or subscription plan),
 * or `null` when there is no pending update.
 */
class UpdateStatus
{
    /**
     * An update was submitted and is awaiting review.
     */
    public const PENDING = "pending";

    /**
     * The submitted update is being reviewed.
     */
    public const REVIEWING = "reviewing";
}
