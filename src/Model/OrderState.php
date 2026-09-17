<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/**
 * The states of an order the contract lists — `Order::$state` is a string, compare it with `OrderState::DONE`.
 * A purchase goes `REGISTERED` → `EXECUTING` → `FILLED` → `DELIVERING` → `DONE`; a sale `REGISTERED` → `TRANSFERRING`
 * → `DEPOSITED` → `EXECUTING` → `FILLED` → `DONE`. `PARKED`: executed but nothing was received (terminal); `FAILED`.
 */
final class OrderState
{
    public const REGISTERED = 'REGISTERED';
    public const TRANSFERRING = 'TRANSFERRING';
    public const DEPOSITED = 'DEPOSITED';
    public const EXECUTING = 'EXECUTING';
    public const FILLED = 'FILLED';
    public const DELIVERING = 'DELIVERING';
    public const DONE = 'DONE';
    public const PARKED = 'PARKED';
    public const FAILED = 'FAILED';

    /** Every value, in the order of the contract */
    public const VALUES = [self::REGISTERED, self::TRANSFERRING, self::DEPOSITED, self::EXECUTING, self::FILLED, self::DELIVERING, self::DONE, self::PARKED, self::FAILED];

    private function __construct()
    {
    }
}
