<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Models;

readonly class BankWithdrawAmount
{
    public function __construct(
        public float $requestedAmount,
        public float $finalWithdrawnAmount,
        public float $feesAmount,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            requestedAmount:      (float) $data['requestedAmount'],
            finalWithdrawnAmount: (float) $data['finalWithdrawnAmount'],
            feesAmount:           (float) $data['feesAmount'],
        );
    }
}
