<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** The details of a customer */
final readonly class CustomerAccount
{
    public function __construct(
        public string $email,
        public string $firstname,
        public ?string $lastname,
        /** Tax identification number */
        public ?string $fin,
        /** Date of birth, epoch seconds */
        public ?int $birthdate,
        /** The number as an integer */
        public ?int $phoneNumber,
        /** Dialing code, `+33` by default */
        public ?string $phoneZone,
        public ?AccountAddress $address,
        /** The customer's own referral code, generated at creation */
        public string $referralCode,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $address = Cast::nullableObject($data, 'address');

        return new self(
            Cast::string($data, 'email'),
            Cast::string($data, 'firstname'),
            Cast::nullableString($data, 'lastname'),
            Cast::nullableString($data, 'fin'),
            Cast::nullableInt($data, 'birthdate'),
            Cast::nullableInt($data, 'phoneNumber'),
            Cast::nullableString($data, 'phoneZone'),
            $address === null ? null : AccountAddress::fromArray($address),
            Cast::string($data, 'referralCode'),
        );
    }
}
