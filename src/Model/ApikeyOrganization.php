<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** The organization of a key — `{ uuid, state, name, hub, owner }` */
final readonly class ApikeyOrganization
{
    public function __construct(
        public string $uuid,
        public string $state,
        public string $name,
        public ?ApikeyHub $hub,
        public ?ApikeyOwner $owner,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $hub = Cast::nullableObject($data, 'hub');
        $owner = Cast::nullableObject($data, 'owner');

        return new self(
            Cast::string($data, 'uuid'),
            Cast::string($data, 'state'),
            Cast::string($data, 'name'),
            $hub === null ? null : ApikeyHub::fromArray($hub),
            $owner === null ? null : ApikeyOwner::fromArray($owner),
        );
    }
}
