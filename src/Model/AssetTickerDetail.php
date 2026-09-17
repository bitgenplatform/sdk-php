<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** `GET /ticker/{iso}`: the ticker of an asset and its EUR price history */
final readonly class AssetTickerDetail
{
    public function __construct(
        public string $iso,
        public AssetTicker $ticker,
        public History $history,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            Cast::string($data, 'iso'),
            AssetTicker::fromArray(Cast::object($data, 'ticker')),
            History::fromArray(Cast::object($data, 'history')),
        );
    }
}
