<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** Gas cost of a transfer: `gas` in the smallest unit of the native coin, `native` in native coin units */
final readonly class AssetFeesComputed
{
    public function __construct(
        public string $gas,
        public string $native,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(Cast::string($data, 'gas'), Cast::string($data, 'native'));
    }
}
