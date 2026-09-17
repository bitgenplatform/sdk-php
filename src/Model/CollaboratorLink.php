<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** The attachment of a customer to an organization */
final readonly class CollaboratorLink
{
    /**
     * @param list<string> $roles `ROLE_USER` for a customer
     */
    public function __construct(
        public string $uuid,
        /** `WAIT` until activation, then `ENABLED`; `REVOKED` once removed */
        public string $state,
        public array $roles,
        /** Organization name */
        public string $organization,
        public string $organizationUuid,
        /** uuid of the collaborator in charge of the customer */
        public ?string $manager,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            Cast::string($data, 'uuid'),
            Cast::string($data, 'state'),
            Cast::strings($data, 'roles'),
            Cast::string($data, 'organization'),
            Cast::string($data, 'organizationUuid'),
            Cast::nullableString($data, 'manager'),
        );
    }
}
