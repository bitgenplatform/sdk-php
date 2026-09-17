<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** The network of an asset — `caip2` is the chain identifier (`eip155:1`), `data` an internal connector mapping, raw JSON */
final readonly class AssetNetwork
{
    public function __construct(
        public string $uuid,
        public string $state,
        public string $caip2,
        public string $label,
        public int $gasBase,
        public string $data,
        public AssetNetworkType $type,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            Cast::string($data, 'uuid'),
            Cast::string($data, 'state'),
            Cast::string($data, 'caip2'),
            Cast::string($data, 'label'),
            Cast::int($data, 'gasBase'),
            Cast::string($data, 'data'),
            AssetNetworkType::fromArray(Cast::object($data, 'type')),
        );
    }
}
