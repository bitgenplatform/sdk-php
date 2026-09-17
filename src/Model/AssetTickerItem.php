<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** An item of `GET /ticker`: the `iso` of an asset and its ticker */
final readonly class AssetTickerItem
{
    public function __construct(
        public string $iso,
        public AssetTicker $ticker,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(Cast::string($data, 'iso'), AssetTicker::fromArray(Cast::object($data, 'ticker')));
    }
}
