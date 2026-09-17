<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** The states of a connector — `Core::$state` is a string; also the `state` filter of the catalogue */
final class CoreState
{
    public const ENABLED = 'ENABLED';
    public const DISABLED = 'DISABLED';

    /** Every value, in the order of the contract */
    public const VALUES = [self::ENABLED, self::DISABLED];

    private function __construct()
    {
    }
}
