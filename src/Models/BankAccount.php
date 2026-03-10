<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Models;

readonly class BankAccount
{
    public function __construct(
        public float  $amount,
        public string $ref,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            amount: (float)  $data['amount'],
            ref:    (string) $data['ref'],
        );
    }
}
