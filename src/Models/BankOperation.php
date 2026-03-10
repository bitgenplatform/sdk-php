<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Models;

readonly class BankOperation
{
    public function __construct(
        public string $txId,
        public float  $amount,
        public string $direction,
        public int    $date,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            txId:      (string) $data['txId'],
            amount:    (float)  $data['amount'],
            direction: (string) $data['direction'],
            date:      (int)    $data['date'],
        );
    }
}
