<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Models;

readonly class UserAccount
{
    public function __construct(
        public int          $createdAt,
        public string       $email,
        public string       $firstname,
        public string       $lastname,
        public ?int         $birthdate,
        public ?UserAddress $address,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            createdAt: (int)    $data['createdAt'],
            email:     (string) $data['email'],
            firstname: (string) $data['firstname'],
            lastname:  (string) $data['lastname'],
            birthdate: isset($data['birthdate']) ? (int) $data['birthdate'] : null,
            address:   isset($data['address']) ? UserAddress::fromArray($data['address']) : null,
        );
    }
}
