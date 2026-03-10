<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Models;

readonly class Wallet
{
    public function __construct(
        public WalletAsset $asset,
        public string      $address,
        public ?string     $tag,
        public float       $balance,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            asset:   WalletAsset::fromArray($data['asset']),
            address: (string) $data['address'],
            tag:     $data['tag'] ?? null,
            balance: (float)  $data['balance'],
        );
    }
}
