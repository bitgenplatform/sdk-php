<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Models;

readonly class UserListItem
{
    public function __construct(
        public string       $uuid,
        public string       $state,
        public UserAccount  $account,
        public UserIdentity $identity,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            uuid:     (string) $data['uuid'],
            state:    (string) $data['state'],
            account:  UserAccount::fromArray($data['account']),
            identity: UserIdentity::fromArray($data['identity']),
        );
    }
}
