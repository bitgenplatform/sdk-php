<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** The states of a customer the contract lists — `Customer::$state` is a string, compare it with `CustomerState::ENABLED` */
final class CustomerState
{
    public const CREATED = 'CREATED';
    public const ENABLED = 'ENABLED';
    public const CLOSED = 'CLOSED';
    public const FROZEN = 'FROZEN';

    /** Every value, in the order of the contract */
    public const VALUES = [self::CREATED, self::ENABLED, self::CLOSED, self::FROZEN];

    private function __construct()
    {
    }
}
