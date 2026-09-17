<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** `KYC` (a person) or `KYB` (a business) — `Identity::$mode` is a string */
final class IdentityMode
{
    public const KYC = 'KYC';
    public const KYB = 'KYB';

    /** Every value, in the order of the contract */
    public const VALUES = [self::KYC, self::KYB];

    private function __construct()
    {
    }
}
