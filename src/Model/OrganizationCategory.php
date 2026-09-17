<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/**
 * Category of a customer — the `$organization` of `CustomerResource::create()` (`CUSTOMER` by default; `B2B` also
 * opens a KYB file) and `CustomerSetup::$choosenOrganization` (a string on the API's side)
 */
final class OrganizationCategory
{
    public const CUSTOMER = 'CUSTOMER';
    public const B2B = 'B2B';

    /** Every value, in the order of the contract */
    public const VALUES = [self::CUSTOMER, self::B2B];

    private function __construct()
    {
    }
}
