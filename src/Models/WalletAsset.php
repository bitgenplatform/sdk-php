<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Models;

readonly class WalletAsset
{
    public function __construct(
        public string $iso,
        public string $mode,
        public ?int   $baseUnit,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            iso:      (string) $data['iso'],
            mode:     (string) $data['mode'],
            baseUnit: isset($data['baseUnit']) ? (int) $data['baseUnit'] : null,
        );
    }
}
