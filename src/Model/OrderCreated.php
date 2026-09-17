<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** `POST /trading` — the uuid of the order (`tunnel`) and its initial state */
final readonly class OrderCreated
{
    public function __construct(
        /** The uuid of the order: read it with `$client->trading->get()` */
        public string $tunnel,
        /** `OrderState` lists the known values */
        public string $state,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(Cast::string($data, 'tunnel'), Cast::string($data, 'state'));
    }
}
