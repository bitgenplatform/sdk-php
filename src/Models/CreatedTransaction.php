<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Models;

readonly class CreatedTransaction
{
    public function __construct(
        public string $txId,
        public string $state,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            txId:  (string) $data['txId'],
            state: (string) $data['state'],
        );
    }
}
