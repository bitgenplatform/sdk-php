<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/**
 * A time series of the API: `d` covers the last 24 hours with one point per hour, `w` and `m` one point
 * per day, `y` and `all` one point per month. Each point is `[epoch seconds, value]`, the last one is the
 * current value.
 */
final readonly class History
{
    /**
     * @param list<array{int, float}> $d
     * @param list<array{int, float}> $w
     * @param list<array{int, float}> $m
     * @param list<array{int, float}> $y
     * @param list<array{int, float}> $all
     */
    public function __construct(
        public array $d,
        public array $w,
        public array $m,
        public array $y,
        public array $all,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            self::points($data, 'd'),
            self::points($data, 'w'),
            self::points($data, 'm'),
            self::points($data, 'y'),
            self::points($data, 'all'),
        );
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return list<array{int, float}>
     */
    private static function points(array $data, string $key): array
    {
        $points = [];
        foreach (Cast::list($data, $key) as $point) {
            if (is_array($point) && count($point) >= 2) {
                $values = array_values($point);
                $points[] = [is_numeric($values[0]) ? (int) $values[0] : 0, is_numeric($values[1]) ? (float) $values[1] : 0.0];
            }
        }

        return $points;
    }
}
