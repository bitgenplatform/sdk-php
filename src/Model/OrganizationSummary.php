<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** An organization as a transaction or a staking movement references it — `{ uuid, state, name, hub? }` */
final readonly class OrganizationSummary
{
    public function __construct(
        public string $uuid,
        public string $state,
        public string $name,
        /** The hub the organization belongs to — null when it has none, and never given on a staking movement */
        public ?OrganizationHub $hub,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $hub = Cast::nullableObject($data, 'hub');

        return new self(
            Cast::string($data, 'uuid'),
            Cast::string($data, 'state'),
            Cast::string($data, 'name'),
            $hub === null ? null : OrganizationHub::fromArray($hub),
        );
    }
}
