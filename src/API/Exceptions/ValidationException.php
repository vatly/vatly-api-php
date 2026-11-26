<?php

declare(strict_types=1);

namespace Vatly\API\Exceptions;

/**
 * The Vatly API answered `422`: the request was well-formed but invalid. {@see getErrors()} lists the messages per field.
 */
class ValidationException extends ApiException
{
}
