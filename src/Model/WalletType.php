<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** `USER`: the wallet of a customer — `TREASURY`: a wallet of the organization itself (read-only). `Wallet::$type` is a string. */
final class WalletType
{
    public const USER = 'USER';
    public const TREASURY = 'TREASURY';

    /** Every value, in the order of the contract */
    public const VALUES = [self::USER, self::TREASURY];

    private function __construct()
    {
    }
}
