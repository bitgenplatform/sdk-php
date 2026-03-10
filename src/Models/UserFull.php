<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Models;

readonly class UserFull
{
    public function __construct(
        public string       $uuid,
        public UserIdentity $identity,
        public UserAccount  $account,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            uuid:     (string) $data['uuid'],
            identity: UserIdentity::fromArray($data['identity']),
            account:  UserAccount::fromArray($data['account']),
        );
    }
}
