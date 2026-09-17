<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** The kinds of staking movements — `StakingMovement::$kind` is a string; also the `direction` filter of the lists */
final class StakingMovementKind
{
    public const STAKE = 'STAKE';
    public const UNSTAKE = 'UNSTAKE';
    public const WITHDRAW = 'WITHDRAW';
    public const REWARD = 'REWARD';

    /** Every value, in the order of the contract */
    public const VALUES = [self::STAKE, self::UNSTAKE, self::WITHDRAW, self::REWARD];

    private function __construct()
    {
    }
}
