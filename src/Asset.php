<?php

declare(strict_types=1);

namespace Bitgen\Sdk;

/**
 * ISO codes of the main assets, as string constants (`Asset::ETH` is `'eth'`), accepted wherever an asset is expected (the API takes a uuid or an iso in any case).
 * The `iso` returned by the API has its stored case (`ETH` today): compare it case-insensitively.
 * Provisional list — extended once the production list is confirmed.
 */
final class Asset
{
    public const BTC = 'btc';
    public const ETH = 'eth';
    public const USDC = 'usdc';
    public const XRP = 'xrp';
    public const SOL = 'sol';

    /** Every value, in the order of the contract */
    public const VALUES = [self::BTC, self::ETH, self::USDC, self::XRP, self::SOL];

    private function __construct()
    {
    }
}
