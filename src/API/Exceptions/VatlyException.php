<?php

declare(strict_types=1);

namespace Vatly\API\Exceptions;

use Throwable;

/**
 * Marker for every exception this package throws, so a single
 * `catch (VatlyException $e)` covers API errors and webhook signature failures.
 */
interface VatlyException extends Throwable
{
}
