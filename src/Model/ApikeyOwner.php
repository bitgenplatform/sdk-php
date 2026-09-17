<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** The owner of the organization of a key — `{ uuid, login, firstname, lastname }` */
final readonly class ApikeyOwner
{
    public function __construct(
        public string $uuid,
        /** The email */
        public string $login,
        public string $firstname,
        public ?string $lastname,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(Cast::string($data, 'uuid'), Cast::string($data, 'login'), Cast::string($data, 'firstname'), Cast::nullableString($data, 'lastname'));
    }
}
