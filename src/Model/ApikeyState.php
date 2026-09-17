<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** States of an API key — `Apikey::$state` is a string; `ApikeysResource::list()` leaves the `REVOKED` ones out unless `$includeRevoked` */
final class ApikeyState
{
    public const ENABLED = 'ENABLED';
    public const REVOKED = 'REVOKED';

    /** Every value, in the order of the contract */
    public const VALUES = [self::ENABLED, self::REVOKED];

    private function __construct()
    {
    }
}
