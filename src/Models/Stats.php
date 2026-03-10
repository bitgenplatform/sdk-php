<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Models;

readonly class Stats
{
    public function __construct(
        public UsersStats $users,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            users: UsersStats::fromArray($data['users']),
        );
    }
}
