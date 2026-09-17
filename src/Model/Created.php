<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** `{ uuid }` — what a creation answers. Carries a `uuid`: it is accepted wherever a customer is expected. */
final readonly class Created
{
    public function __construct(
        public string $uuid,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(Cast::string($data, 'uuid'));
    }
}
