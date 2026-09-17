<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** Filter of the EUR operations — `ALL` (default), or one kind */
final class BankDirection
{
    public const ALL = 'ALL';
    public const DEPOSIT = 'DEPOSIT';
    public const WITHDRAWAL = 'WITHDRAWAL';
    public const PURCHASE = 'PURCHASE';
    public const SELL = 'SELL';

    /** Every value, in the order of the contract */
    public const VALUES = [self::ALL, self::DEPOSIT, self::WITHDRAWAL, self::PURCHASE, self::SELL];

    private function __construct()
    {
    }
}
