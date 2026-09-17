<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** The postal address record of a customer — `state` is set on `Customer::$account`, null on `Account::$account` (reduced to `{ uuid, address }`) */
final readonly class AccountAddress
{
    public function __construct(
        public string $uuid,
        public string $address,
        public ?string $state,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(Cast::string($data, 'uuid'), Cast::string($data, 'address'), Cast::nullableString($data, 'state'));
    }
}
