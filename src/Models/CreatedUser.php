<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Models;

readonly class CreatedUser
{
    public function __construct(
        public string $uuid,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(uuid: (string) $data['uuid']);
    }
}
