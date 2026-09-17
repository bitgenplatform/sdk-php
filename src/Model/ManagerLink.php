<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** An attachment where the account manages other people — always empty for a customer (CRM data) */
final readonly class ManagerLink
{
    /**
     * @param list<string> $roles
     */
    public function __construct(
        public string $uuid,
        public string $state,
        public mixed $mandate,
        /** `d/m/Y` */
        public ?string $mandatedUntil,
        /** Email */
        public ?string $account,
        public string $organizationUuid,
        public array $roles,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            Cast::string($data, 'uuid'),
            Cast::string($data, 'state'),
            Cast::raw($data, 'mandate'),
            Cast::nullableString($data, 'mandatedUntil'),
            Cast::nullableString($data, 'account'),
            Cast::string($data, 'organizationUuid'),
            Cast::strings($data, 'roles'),
        );
    }
}
