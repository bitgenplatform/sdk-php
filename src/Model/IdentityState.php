<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** The states of an identity file the contract lists — `Identity::$state` is a string */
final class IdentityState
{
    public const CREATED = 'CREATED';
    public const IN_PROGRESS = 'IN_PROGRESS';
    public const WAIT = 'WAIT';
    public const PENDING = 'PENDING';
    public const VALIDATED = 'VALIDATED';
    public const REJECTED = 'REJECTED';
    public const FROZEN = 'FROZEN';
    public const EXPIRED = 'EXPIRED';
    public const CLOSED = 'CLOSED';

    /** Every value, in the order of the contract */
    public const VALUES = [self::CREATED, self::IN_PROGRESS, self::WAIT, self::PENDING, self::VALIDATED, self::REJECTED, self::FROZEN, self::EXPIRED, self::CLOSED];

    private function __construct()
    {
    }
}
