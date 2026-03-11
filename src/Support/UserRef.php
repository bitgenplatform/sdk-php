<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Support;

use Bitgen\Sdk\Models\UserFull;
use Bitgen\Sdk\Models\UserListItem;

use InvalidArgumentException;

class UserRef
{
    public static function resolve(string|UserFull|UserListItem $user): string
    {
        return match (true) {
            is_string($user)            => $user,
            $user instanceof UserFull      => $user->uuid,
            $user instanceof UserListItem  => $user->uuid,
            default => throw new InvalidArgumentException('Invalid user reference.'),
        };
    }
}