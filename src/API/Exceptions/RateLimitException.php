<?php

declare(strict_types=1);

namespace Vatly\API\Exceptions;

/**
 * The Vatly API answered `429`: too many requests. Wait {@see getRetryAfter()} seconds before retrying.
 */
class RateLimitException extends ApiException
{
}
