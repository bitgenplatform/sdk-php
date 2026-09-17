<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** The states of a staking movement the contract lists — `StakingMovement::$state` is a string */
final class StakingMovementState
{
    public const REQUESTED = 'REQUESTED';
    public const PENDING = 'PENDING';
    public const COMPLETED = 'COMPLETED';
    public const FAILED = 'FAILED';
    public const CANCELED = 'CANCELED';

    /** Every value, in the order of the contract */
    public const VALUES = [self::REQUESTED, self::PENDING, self::COMPLETED, self::FAILED, self::CANCELED];

    private function __construct()
    {
    }
}
