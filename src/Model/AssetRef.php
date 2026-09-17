<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** The asset of a wallet, an order… — `{ uuid, iso, label }`. `iso` has the case the API stores: compare it case-insensitively. */
final readonly class AssetRef
{
    public function __construct(
        public string $uuid,
        public string $iso,
        public string $label,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            Cast::string($data, 'uuid'),
            Cast::string($data, 'iso'),
            Cast::string($data, 'label'),
        );
    }
}
