<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** Where a transaction comes from: the EUR account (`BANK`) or a custody wallet (`CUSTODY`) — `Transaction::$source` is a string; also a filter of the list */
final class TransactionSource
{
    public const BANK = 'BANK';
    public const CUSTODY = 'CUSTODY';

    /** Every value, in the order of the contract */
    public const VALUES = [self::BANK, self::CUSTODY];

    private function __construct()
    {
    }
}
