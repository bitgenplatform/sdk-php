<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Support;

use InvalidArgumentException;

/**
 * Amounts always reach the API as strings: a string is sent as is (trimmed), an int or a finite float ≥ 0 in its
 * shortest decimal form. Nothing is ever rounded or reformatted — send crypto amounts (up to 18 decimals) as strings.
 *
 * @internal
 */
final class Amount
{
    /**
     * @throws InvalidArgumentException empty string, negative, non-finite, or a float that would be written in
     *                                  exponent notation (`1.0e-8`, `1.0e+21`): pass it as a decimal string
     */
    public static function normalize(string|int|float $value): string
    {
        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                throw new InvalidArgumentException('amount must be a non-empty string or a finite number >= 0');
            }

            return $trimmed;
        }
        if (is_int($value)) {
            if ($value < 0) {
                throw new InvalidArgumentException('amount must be a non-empty string or a finite number >= 0');
            }

            return (string) $value;
        }
        if (!is_finite($value) || $value < 0) {
            throw new InvalidArgumentException('amount must be a non-empty string or a finite number >= 0');
        }
        if ($value == 0) {
            return '0';
        }
        // Shortest round-trip representation (serialize_precision -1), as json_encode writes it
        $text = json_encode($value, JSON_THROW_ON_ERROR);
        if (str_contains($text, 'e') || str_contains($text, 'E')) {
            throw new InvalidArgumentException(sprintf('amount %s would be sent in exponent notation: pass it as a decimal string', $text));
        }

        return $text;
    }
}
