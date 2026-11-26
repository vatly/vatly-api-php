<?php

declare(strict_types=1);

namespace Vatly\API\Exceptions;

/**
 * The Vatly API answered `404`: the resource does not exist, or not in the API key's mode.
 */
class NotFoundException extends ApiException
{
}
