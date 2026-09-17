<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** The states of a transaction the contract lists (`TRANSFERING` is the API's spelling) — `Transaction::$state` is a string; also the `status` filter of the list */
final class TransactionState
{
    public const ANALYZING = 'ANALYZING';
    public const PENDING = 'PENDING';
    public const COMPLETED = 'COMPLETED';
    public const FROZEN = 'FROZEN';
    public const FAILED = 'FAILED';
    public const TRANSFERING = 'TRANSFERING';
    public const SEIZED = 'SEIZED';

    /** Every value, in the order of the contract */
    public const VALUES = [self::ANALYZING, self::PENDING, self::COMPLETED, self::FROZEN, self::FAILED, self::TRANSFERING, self::SEIZED];

    private function __construct()
    {
    }
}
