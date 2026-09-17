<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/**
 * An asset of the catalogue (`GET /asset`, `GET /asset/{asset}`). `iso` has the case the API stores
 * (`ETH` today): compare it case-insensitively. `baseUnit` is the number of decimals an amount may carry.
 */
final readonly class Asset
{
    public function __construct(
        public string $uuid,
        /** `AssetState` lists the known values: `AVAILABLE`, `UNAVAILABLE`, `ARCHIVED`, `HIDDEN` — a value the contract does not list yet is kept as is */
        public string $state,
        public string $iso,
        public string $label,
        /** `''` for a native asset, never null */
        public string $contractAddress,
        public int $baseUnit,
        public int $gasUnit,
        public ?string $logo,
        /** Internal connector mapping, raw JSON */
        public string $data,
        public AssetFees $fees,
        public AssetTicker $ticker,
        /** EUR price */
        public History $history,
        public AssetNetwork $network,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            Cast::string($data, 'uuid'),
            Cast::string($data, 'state'),
            Cast::string($data, 'iso'),
            Cast::string($data, 'label'),
            Cast::string($data, 'contractAddress'),
            Cast::int($data, 'baseUnit'),
            Cast::int($data, 'gasUnit'),
            Cast::nullableString($data, 'logo'),
            Cast::string($data, 'data'),
            AssetFees::fromArray(Cast::object($data, 'fees')),
            AssetTicker::fromArray(Cast::object($data, 'ticker')),
            History::fromArray(Cast::object($data, 'history')),
            AssetNetwork::fromArray(Cast::object($data, 'network')),
        );
    }
}
