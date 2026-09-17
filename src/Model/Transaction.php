<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/**
 * One entry of the transaction journal: a fiat or crypto movement of the organization, created by the platform
 * (`GET /transaction`, `GET /transaction/{transaction}`).
 */
final readonly class Transaction
{
    /**
     * @param array<string, mixed> $data additional context set by the platform — varies with the transaction
     */
    public function __construct(
        public string $uuid,
        /** `TransactionState` lists the known values: `ANALYZING`, `PENDING`, `COMPLETED`, `FROZEN`, `FAILED`, `TRANSFERING`, `SEIZED` */
        public string $state,
        /** `BANK` (EUR) or `CUSTODY` (crypto) — `TransactionSource` */
        public string $source,
        /** `IN` or `OUT` — `TransactionDirection` */
        public string $direction,
        /** The asset ISO code — `EUR` for a bank transaction */
        public string $asset,
        /** The amount, in that asset — a number: for crypto, the exact amount is the string held by the wallet */
        public float $amount,
        /** EUR value when recorded */
        public ?float $eurValue,
        public ?string $reference,
        /** Whether the customer's balance (EUR account or wallet) has been credited */
        public bool $credited,
        /** An internal leg (staking, sale), not an operation of the customer */
        public bool $silent,
        public array $data,
        public int $createdAt,
        public int $updatedAt,
        /** The customer */
        public ?UserSummary $owner,
        /** The compliance officer assigned while the transaction is on hold */
        public ?UserSummary $assignee,
        public ?OrganizationSummary $organization,
        public ?TransactionAlert $alert,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $owner = Cast::nullableObject($data, 'owner');
        $assignee = Cast::nullableObject($data, 'assignee');
        $organization = Cast::nullableObject($data, 'organization');
        $alert = Cast::nullableObject($data, 'alert');

        return new self(
            Cast::string($data, 'uuid'),
            Cast::string($data, 'state'),
            Cast::string($data, 'source'),
            Cast::string($data, 'direction'),
            Cast::string($data, 'asset'),
            Cast::float($data, 'amount'),
            Cast::nullableFloat($data, 'eurValue'),
            Cast::nullableString($data, 'reference'),
            Cast::bool($data, 'credited'),
            Cast::bool($data, 'silent'),
            Cast::object($data, 'data'),
            Cast::int($data, 'createdAt'),
            Cast::int($data, 'updatedAt'),
            $owner === null ? null : UserSummary::fromArray($owner),
            $assignee === null ? null : UserSummary::fromArray($assignee),
            $organization === null ? null : OrganizationSummary::fromArray($organization),
            $alert === null ? null : TransactionAlert::fromArray($alert),
        );
    }
}
