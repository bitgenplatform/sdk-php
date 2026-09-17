<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** `Customer::$collaborations` — the customer's attachments (`collaborator`) and the attachments they manage (`manager`) */
final readonly class CustomerCollaborations
{
    /**
     * @param list<CollaboratorLink> $collaborator
     * @param list<ManagerLink>      $manager
     */
    public function __construct(
        public array $collaborator,
        public array $manager,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            Cast::objects($data, 'collaborator', CollaboratorLink::fromArray(...)),
            Cast::objects($data, 'manager', ManagerLink::fromArray(...)),
        );
    }
}
