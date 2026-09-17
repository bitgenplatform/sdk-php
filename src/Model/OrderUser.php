<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** The customer of an order — `{ uuid, login }`. Carries a `uuid`: it is accepted wherever a customer is expected. */
final readonly class OrderUser
{
    public function __construct(
        public string $uuid,
        /** The email */
        public string $login,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(Cast::string($data, 'uuid'), Cast::string($data, 'login'));
    }
}
