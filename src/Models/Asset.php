<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Models;

readonly class Asset
{
    public function __construct(
        public string $name,
        public string $iso,
        public int    $rank,
        public float  $price,
        public float  $marketcap,
        public float  $percentChange24h,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name:             (string) $data['name'],
            iso:              (string) $data['iso'],
            rank:             (int)    $data['rank'],
            price:            (float)  $data['price'],
            marketcap:        (float)  $data['marketcap'],
            percentChange24h: (float)  $data['percentChange24h'],
        );
    }
}
