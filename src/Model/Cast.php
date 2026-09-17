<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

use UnexpectedValueException;

/**
 * Reads the decoded answer of the API by key, cast to the type of the contract. A missing key or a value
 * of another type never throws: nullable fields become null, the others the empty value of their type.
 *
 * @internal
 */
final class Cast
{
    /** @param array<string, mixed> $data */
    public static function string(array $data, string $key): string
    {
        $value = $data[$key] ?? null;

        return is_scalar($value) ? (string) $value : '';
    }

    /** @param array<string, mixed> $data */
    public static function nullableString(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        return is_scalar($value) ? (string) $value : null;
    }

    /** @param array<string, mixed> $data */
    public static function int(array $data, string $key): int
    {
        $value = $data[$key] ?? null;
        if (is_int($value)) {
            return $value;
        }

        return is_numeric($value) ? (int) $value : 0;
    }

    /** @param array<string, mixed> $data */
    public static function nullableInt(array $data, string $key): ?int
    {
        $value = $data[$key] ?? null;
        if (is_int($value)) {
            return $value;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    /** @param array<string, mixed> $data */
    public static function float(array $data, string $key): float
    {
        $value = $data[$key] ?? null;

        return is_numeric($value) ? (float) $value : 0.0;
    }

    /** @param array<string, mixed> $data */
    public static function nullableFloat(array $data, string $key): ?float
    {
        $value = $data[$key] ?? null;

        return is_numeric($value) ? (float) $value : null;
    }

    /** @param array<string, mixed> $data */
    public static function bool(array $data, string $key): bool
    {
        return (bool) ($data[$key] ?? false);
    }

    /**
     * A nested object, as an associative array — `[]` when missing
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public static function object(array $data, string $key): array
    {
        return self::asObject($data[$key] ?? null);
    }

    /**
     * A nested object or null
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>|null
     */
    public static function nullableObject(array $data, string $key): ?array
    {
        $value = $data[$key] ?? null;

        return is_array($value) ? self::asObject($value) : null;
    }

    /**
     * A nested list — `[]` when missing
     *
     * @param array<string, mixed> $data
     *
     * @return list<mixed>
     */
    public static function list(array $data, string $key): array
    {
        $value = $data[$key] ?? null;

        return is_array($value) ? array_values($value) : [];
    }

    /**
     * A list of strings — non-scalar items are dropped
     *
     * @param array<string, mixed> $data
     *
     * @return list<string>
     */
    public static function strings(array $data, string $key): array
    {
        $strings = [];
        foreach (self::list($data, $key) as $item) {
            if (is_scalar($item)) {
                $strings[] = (string) $item;
            }
        }

        return $strings;
    }

    /**
     * A map of strings (`Record<string, string>`) — non-scalar values are dropped
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, string>
     */
    public static function stringMap(array $data, string $key): array
    {
        $map = [];
        foreach (self::object($data, $key) as $name => $value) {
            if (is_scalar($value)) {
                $map[$name] = (string) $value;
            }
        }

        return $map;
    }

    /**
     * A list of objects, each mapped with `$item`
     *
     * @template T
     *
     * @param array<string, mixed>                $data
     * @param callable(array<string, mixed>): T   $item
     *
     * @return list<T>
     */
    public static function objects(array $data, string $key, callable $item): array
    {
        $items = [];
        foreach (self::list($data, $key) as $raw) {
            if (is_array($raw)) {
                $items[] = $item(self::asObject($raw));
            }
        }

        return $items;
    }

    /**
     * An opaque value, kept as decoded
     *
     * @param array<string, mixed> $data
     */
    public static function raw(array $data, string $key): mixed
    {
        return $data[$key] ?? null;
    }

    /**
     * The decoded answer of the API, which must be a JSON object
     *
     * @return array<string, mixed>
     *
     * @throws UnexpectedValueException the API answered something else (a contract violation, not an API error)
     */
    public static function answer(mixed $value): array
    {
        if (!is_array($value)) {
            throw new UnexpectedValueException('the API answered ' . get_debug_type($value) . ' instead of a JSON object');
        }

        return self::asObject($value);
    }

    /**
     * The decoded answer of the API, which must be a JSON array of objects
     *
     * @return list<array<string, mixed>>
     *
     * @throws UnexpectedValueException the API answered something else (a contract violation, not an API error)
     */
    public static function answerList(mixed $value): array
    {
        if (!is_array($value)) {
            throw new UnexpectedValueException('the API answered ' . get_debug_type($value) . ' instead of a JSON array');
        }
        if (!array_is_list($value)) {
            throw new UnexpectedValueException('the API answered a JSON object instead of a JSON array');
        }
        $items = [];
        foreach ($value as $item) {
            if (!is_array($item)) {
                throw new UnexpectedValueException('the API answered a list with an item that is not an object');
            }
            $items[] = self::asObject($item);
        }

        return $items;
    }

    /**
     * Any decoded value as an associative array — `[]` when it is not an object
     *
     * @return array<string, mixed>
     */
    public static function asObject(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }
        $object = [];
        foreach ($value as $key => $item) {
            $object[(string) $key] = $item;
        }

        return $object;
    }
}
