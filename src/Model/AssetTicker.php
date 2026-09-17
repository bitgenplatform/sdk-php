<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** Live market data of an asset — `price` and `marketcap` in EUR, `percentChange24h` in % */
final readonly class AssetTicker
{
    public function __construct(
        public float $price,
        public float $marketcap,
        public int $rank,
        public float $percentChange24h,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            Cast::float($data, 'price'),
            Cast::float($data, 'marketcap'),
            Cast::int($data, 'rank'),
            Cast::float($data, 'percentChange24h'),
        );
    }
}
