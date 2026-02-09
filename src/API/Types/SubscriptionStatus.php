<?php

namespace Vatly\API\Types;

class SubscriptionStatus
{
    /**
     * Subscription is active and will renew.
     */
    public const ACTIVE = "active";

    /**
     * Subscription has been created but not yet started.
     */
    public const CREATED = "created";

    /**
     * Subscription is in trial period.
     */
    public const TRIAL = "trial";

    /**
     * Subscription is canceled but still active until period ends.
     */
    public const ON_GRACE_PERIOD = "on_grace_period";

    /**
     * Subscription is temporarily paused.
     */
    public const PAUSED = "paused";

    /**
     * Subscription has been canceled.
     */
    public const CANCELED = "canceled";
}
