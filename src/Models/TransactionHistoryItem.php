<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Models;

readonly class TransactionHistoryItem
{
    public function __construct(
        public string $asset,
        public string $direction,
        public float  $amount,
        public int    $date,
        public float  $usdTokenPrice,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            asset:         (string) $data['asset'],
            direction:     (string) $data['direction'],
            amount:        (float)  $data['amount'],
            date:          (int)    $data['date'],
            usdTokenPrice: (float)  $data['usdTokenPrice'],
        );
    }
}
