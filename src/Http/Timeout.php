<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Http;

use InvalidArgumentException;

/**
 * The request timeout: seconds in the configuration, whole milliseconds for curl.
 *
 * @internal
 */
final class Timeout
{
    /** Default, in seconds */
    public const DEFAULT = 30;
    /** Largest value, in seconds: curl takes milliseconds in a 32-bit integer */
    public const MAX = 2_147_483;

    /**
     * `0` = no timeout; any positive value is at least 1 ms.
     *
     * @throws InvalidArgumentException not a finite number between 0 and 2147483
     */
    public static function toMilliseconds(int|float $seconds): int
    {
        if ((is_float($seconds) && !is_finite($seconds)) || $seconds < 0 || $seconds > self::MAX) {
            throw new InvalidArgumentException(sprintf('timeout must be a number of seconds between 0 (no timeout) and %d', self::MAX));
        }
        if ($seconds == 0) {
            return 0;
        }

        return max(1, (int) round($seconds * 1000));
    }
}
