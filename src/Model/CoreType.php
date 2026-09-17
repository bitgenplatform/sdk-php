<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** The kinds of connectors — `Core::$type` is a string; also the `type` filter of the catalogue */
final class CoreType
{
    /** Identity verification service */
    public const IDENTITY = 'IDENTITY';
    /** Anti-money-laundering service */
    public const AML = 'AML';
    /** Exchange */
    public const TRADING = 'TRADING';
    /** Custodian */
    public const CUSTODY = 'CUSTODY';
    /** Staking provider */
    public const STAKING = 'STAKING';
    /** Bank */
    public const RAMP = 'RAMP';

    /** Every value, in the order of the contract */
    public const VALUES = [self::IDENTITY, self::AML, self::TRADING, self::CUSTODY, self::STAKING, self::RAMP];

    private function __construct()
    {
    }
}
