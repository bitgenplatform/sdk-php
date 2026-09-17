<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** The family of a network (`code`: `UTXO`, `EVM`, `COMPUTE_UNIT`, `DROPS`) — `data` is an internal connector mapping, raw JSON */
final readonly class AssetNetworkType
{
    public function __construct(
        public string $uuid,
        public string $code,
        public string $label,
        public string $data,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            Cast::string($data, 'uuid'),
            Cast::string($data, 'code'),
            Cast::string($data, 'label'),
            Cast::string($data, 'data'),
        );
    }
}
