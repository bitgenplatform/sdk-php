<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** The identity of a `UserSummary` — `{ firstname, lastname, fin }` */
final readonly class UserSummaryAccount
{
    public function __construct(
        public string $firstname,
        public ?string $lastname,
        /** Tax identification number */
        public ?string $fin,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(Cast::string($data, 'firstname'), Cast::nullableString($data, 'lastname'), Cast::nullableString($data, 'fin'));
    }
}
