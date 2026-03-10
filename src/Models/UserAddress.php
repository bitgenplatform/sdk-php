<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Models;

readonly class UserAddress
{
    public function __construct(
        public string $street,
        public string $postalCode,
        public string $city,
        public string $country,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            street:     (string) $data['street'],
            postalCode: (string) $data['postalCode'],
            city:       (string) $data['city'],
            country:    (string) $data['country'],
        );
    }
}
