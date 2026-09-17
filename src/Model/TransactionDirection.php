<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** The direction of a transaction — `Transaction::$direction` is a string; also a filter of the list */
final class TransactionDirection
{
    public const IN = 'IN';
    public const OUT = 'OUT';

    /** Every value, in the order of the contract */
    public const VALUES = [self::IN, self::OUT];

    private function __construct()
    {
    }
}
