<?php

declare(strict_types=1);

namespace Bitgen\Sdk;

/**
 * Target environment — `new BitgenClient(env: Env::SANDBOX, …)`; the constants are the strings the API knows (`Env::VALUES`).
 */
final class Env
{
    public const PRODUCTION = 'production';
    public const SANDBOX = 'sandbox';
    public const STAGING = 'staging';
    public const LOCALHOST = 'localhost';

    /** Every value, in the order of the contract */
    public const VALUES = [self::PRODUCTION, self::SANDBOX, self::STAGING, self::LOCALHOST];

    private function __construct()
    {
    }
}
