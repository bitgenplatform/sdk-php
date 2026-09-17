<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** `PUT /bank/{user}` — the uuid of the transaction created for the withdrawal */
final readonly class BankWithdrawal
{
    public function __construct(
        public string $transaction,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(Cast::string($data, 'transaction'));
    }
}
