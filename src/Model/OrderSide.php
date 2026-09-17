<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** The side of an order — `Order::$side` is a string, compare it with `OrderSide::BUY` */
final class OrderSide
{
    public const BUY = 'BUY';
    public const SELL = 'SELL';

    /** Every value, in the order of the contract */
    public const VALUES = [self::BUY, self::SELL];

    private function __construct()
    {
    }
}
