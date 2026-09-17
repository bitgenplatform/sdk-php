<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** The states of a wallet the contract lists — `Wallet::$state` is a string, compare it with `WalletState::FROZEN` */
final class WalletState
{
    public const CREATED = 'CREATED';
    public const FROZEN = 'FROZEN';

    /** Every value, in the order of the contract */
    public const VALUES = [self::CREATED, self::FROZEN];

    private function __construct()
    {
    }
}
