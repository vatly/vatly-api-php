<?php

declare(strict_types=1);

namespace Vatly\API\Exceptions;

/**
 * The Vatly API answered `401`: the API key is missing or invalid.
 */
class AuthenticationException extends ApiException
{
}
