<?php

declare(strict_types=1);

namespace Vatly\API\Exceptions;

/**
 * The Vatly API answered `409`, for example an idempotency key reused with other parameters or a request still in progress. Read {@see getDetails()} and {@see getRetryAfter()} to decide whether to retry.
 */
class ConflictException extends ApiException
{
}
