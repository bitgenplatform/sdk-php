<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Models;

readonly class CreatedOrder
{
    public function __construct(
        public string $tunnel,
        public string $state,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tunnel: (string) $data['tunnel'],
            state:  (string) $data['state'],
        );
    }
}
