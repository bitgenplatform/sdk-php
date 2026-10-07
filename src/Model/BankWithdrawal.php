<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** `PUT /bank/{user}` — the identifier of the withdrawal; its line in the transaction journal is opened by the compliance analysis, within a minute of the call */
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
