<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** One EUR operation of an account (`GET /bank/{user}/operations`) */
final readonly class BankOperation
{
    public function __construct(
        /** Identifier of the ledger entry */
        public string $txId,
        /** EUR */
        public float $amount,
        /** `DEPOSIT`, `WITHDRAWAL`, `PURCHASE` or `SELL` (`BankDirection`) */
        public string $direction,
        public int $date,
        /** Free label of the operation — for instance the asset bought or sold */
        public ?string $info,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            Cast::string($data, 'txId'),
            Cast::float($data, 'amount'),
            Cast::string($data, 'direction'),
            Cast::int($data, 'date'),
            Cast::nullableString($data, 'info'),
        );
    }
}
