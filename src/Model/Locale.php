<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** Language of the BITGEN web application and of the emails — `FR` (default) or `EN` */
final class Locale
{
    public const FR = 'FR';
    public const EN = 'EN';

    /** Every value, in the order of the contract */
    public const VALUES = [self::FR, self::EN];

    private function __construct()
    {
    }
}
