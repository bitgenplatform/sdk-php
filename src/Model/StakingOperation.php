<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** One staking operation of a customer (`GET /staking/{user}/operations`) */
final readonly class StakingOperation
{
    public function __construct(
        /** Identifier of the journal entry */
        public string $txId,
        /** The movement — null for a daily reward */
        public ?string $movement,
        /** The asset ISO code */
        public string $asset,
        /** `STAKE`, `UNSTAKE`, `WITHDRAW` or `REWARD` (`StakingMovementKind`) */
        public string $kind,
        /** Asset units, as a string */
        public string $amount,
        /** EUR price of the asset at that time */
        public float $price,
        /** EUR value */
        public float $value,
        /** `created`, `pending`, `validated`, `failed`, `reward`, `claimed`, `unstake`, `closed` — other values may appear */
        public string $event,
        /** The connector name */
        public string $provider,
        /** Epoch seconds */
        public int $date,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            Cast::string($data, 'txId'),
            Cast::nullableString($data, 'movement'),
            Cast::string($data, 'asset'),
            Cast::string($data, 'kind'),
            Cast::string($data, 'amount'),
            Cast::float($data, 'price'),
            Cast::float($data, 'value'),
            Cast::string($data, 'event'),
            Cast::string($data, 'provider'),
            Cast::int($data, 'date'),
        );
    }
}
