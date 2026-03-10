<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Models;

readonly class BankWithdrawResult
{
    public function __construct(
        public string            $txId,
        public BankWithdrawAmount $amount,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            txId:   (string) $data['txId'],
            amount: BankWithdrawAmount::fromArray($data['amount']),
        );
    }
}
