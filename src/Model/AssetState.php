<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** The states of an asset the contract lists — `Asset::$state` is a string, compare it with `AssetState::AVAILABLE` */
final class AssetState
{
    public const AVAILABLE = 'AVAILABLE';
    public const UNAVAILABLE = 'UNAVAILABLE';
    public const ARCHIVED = 'ARCHIVED';
    public const HIDDEN = 'HIDDEN';

    /** Every value, in the order of the contract */
    public const VALUES = [self::AVAILABLE, self::UNAVAILABLE, self::ARCHIVED, self::HIDDEN];

    private function __construct()
    {
    }
}
