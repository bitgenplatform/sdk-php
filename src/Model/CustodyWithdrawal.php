<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** `PUT /custody/{user}` — the uuid of the transaction created for the withdrawal, null while the analysis has not created it yet */
final readonly class CustodyWithdrawal
{
    public function __construct(
        public ?string $transaction,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(Cast::nullableString($data, 'transaction'));
    }
}
