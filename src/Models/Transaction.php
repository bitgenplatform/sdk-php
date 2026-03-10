<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Models;

readonly class Transaction
{
    public function __construct(
        public int     $createdAt,
        public string  $state,
        public float   $amount,
        public string  $owner,
        public ?string $error,
        public string  $targetAddress,
        public ?string $targetTag,
        public string  $asset,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            createdAt:     (int)    $data['createdAt'],
            state:         (string) $data['state'],
            amount:        (float)  $data['amount'],
            owner:         (string) $data['owner'],
            error:         $data['error'] ?? null,
            targetAddress: (string) $data['targetAddress'],
            targetTag:     $data['targetTag'] ?? null,
            asset:         (string) $data['asset'],
        );
    }
}
