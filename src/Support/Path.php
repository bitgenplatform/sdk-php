<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Support;

use InvalidArgumentException;

/**
 * Path segments (`/asset/{asset}`, `/account/{user}`…): validated and encoded before they reach a URL.
 *
 * @internal
 */
final class Path
{
    /**
     * @throws InvalidArgumentException empty, or `.` / `..` (which the URL would normalize away)
     */
    public static function segment(string $value, string $name): string
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException(sprintf('%s must be a non-empty string', $name));
        }
        if ($value === '.' || $value === '..') {
            throw new InvalidArgumentException(sprintf('%s must not be "." or ".."', $name));
        }

        return rawurlencode($value);
    }
}
