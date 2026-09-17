<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** Filter of the orders list (`$client->trading->list()`): only purchases, or only sales — lowercase, as the API expects it */
final class TradingDirection
{
    public const BUY = 'buy';
    public const SELL = 'sell';

    /** Every value, in the order of the contract */
    public const VALUES = [self::BUY, self::SELL];

    private function __construct()
    {
    }
}
