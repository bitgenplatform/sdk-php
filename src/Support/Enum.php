<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Support;

use InvalidArgumentException;

/**
 * The single validation of the constant lists of the SDK (`Env::VALUES`, `Locale::VALUES`…): a string outside the
 * list is a caller's mistake, refused before any request — the value itself is never echoed.
 *
 * @internal
 */
final class Enum
{
    /**
     * @param list<string> $values the `VALUES` constant of the class
     * @param string       $name   the argument, for the message: `locale must be FR or EN`
     *
     * @throws InvalidArgumentException
     */
    public static function ensure(string $value, array $values, string $name): string
    {
        if (!in_array($value, $values, true)) {
            throw new InvalidArgumentException(sprintf('%s must be %s', $name, self::join($values)));
        }

        return $value;
    }

    /**
     * `A, B or C`
     *
     * @param list<string> $values
     */
    private static function join(array $values): string
    {
        $last = (string) array_pop($values);

        return $values === [] ? $last : implode(', ', $values) . ' or ' . $last;
    }
}
