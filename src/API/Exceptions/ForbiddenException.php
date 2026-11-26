<?php

declare(strict_types=1);

namespace Vatly\API\Exceptions;

/**
 * The Vatly API answered `403`: the API key is invalid, revoked, or not allowed to perform this operation.
 */
class ForbiddenException extends ApiException
{
}
