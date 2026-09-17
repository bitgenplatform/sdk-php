<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** `low`, `medium`, `high`: the raw fee schedule of the gas provider, opaque */
final readonly class AssetFees
{
    public function __construct(
        public mixed $low,
        public mixed $medium,
        public mixed $high,
        public AssetFeesComputed $computed,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            Cast::raw($data, 'low'),
            Cast::raw($data, 'medium'),
            Cast::raw($data, 'high'),
            AssetFeesComputed::fromArray(Cast::object($data, 'computed')),
        );
    }
}
