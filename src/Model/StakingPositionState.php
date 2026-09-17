<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** The states of a staking position the contract lists — `StakingPosition::$state` is a string */
final class StakingPositionState
{
    public const CREATED = 'CREATED';
    public const ENABLED = 'ENABLED';
    public const UNSTAKING = 'UNSTAKING';
    public const CLOSED = 'CLOSED';
    public const FAILED = 'FAILED';

    /** Every value, in the order of the contract */
    public const VALUES = [self::CREATED, self::ENABLED, self::UNSTAKING, self::CLOSED, self::FAILED];

    private function __construct()
    {
    }
}
