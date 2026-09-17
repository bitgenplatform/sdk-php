<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** The organization of an order — `{ uuid, name }` */
final readonly class OrderOrganization
{
    public function __construct(
        public string $uuid,
        public string $name,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(Cast::string($data, 'uuid'), Cast::string($data, 'name'));
    }
}
