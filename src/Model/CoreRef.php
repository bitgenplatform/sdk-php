<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** The connector of a staking position — `{ uuid, name, label }` */
final readonly class CoreRef
{
    public function __construct(
        public string $uuid,
        /** The identifier of the connector (`figment_sol`) */
        public string $name,
        public string $label,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(Cast::string($data, 'uuid'), Cast::string($data, 'name'), Cast::string($data, 'label'));
    }
}
