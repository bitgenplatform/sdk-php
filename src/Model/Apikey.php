<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** An API key of the organization (`GET /organization/{organization}/apikeys`) — the raw key itself is never returned */
final readonly class Apikey
{
    /**
     * @param list<string> $permissions what the key is allowed to do, as set by BITGEN
     */
    public function __construct(
        public string $uuid,
        /** `ENABLED` or `REVOKED` */
        public string $state,
        /** Label given at creation */
        public string $name,
        public array $permissions,
        /** Epoch seconds */
        public int $expireAt,
        /** Epoch seconds */
        public int $createdAt,
        public ApikeyOrganization $organization,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            Cast::string($data, 'uuid'),
            Cast::string($data, 'state'),
            Cast::string($data, 'name'),
            Cast::strings($data, 'permissions'),
            Cast::int($data, 'expireAt'),
            Cast::int($data, 'createdAt'),
            ApikeyOrganization::fromArray(Cast::object($data, 'organization')),
        );
    }
}
