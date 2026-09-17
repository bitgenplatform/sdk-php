<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** The EUR account of a customer (`GET /bank/{user}`), created on first read */
final readonly class BankAccount
{
    public function __construct(
        public string $uuid,
        /** The wire transfer reference the customer must indicate (`BTGN` prefix) */
        public string $message,
        public ?string $iban,
        public ?string $bank,
        public ?string $bic,
        /** EUR */
        public float $balance,
        /** EUR balance curve, materialized every hour — null until it has run for this account */
        public ?History $history,
        public BankPending $pending,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $history = Cast::object($data, 'history');

        return new self(
            Cast::string($data, 'uuid'),
            Cast::string($data, 'message'),
            Cast::nullableString($data, 'iban'),
            Cast::nullableString($data, 'bank'),
            Cast::nullableString($data, 'bic'),
            Cast::float($data, 'balance'),
            $history === [] ? null : History::fromArray($history),
            BankPending::fromArray(Cast::object($data, 'pending')),
        );
    }
}
