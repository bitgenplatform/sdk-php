<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** The position carried by a movement: the capital placed with a provider — its `uuid` is what `rewards` / `unstake` take */
final readonly class StakingPosition
{
    public function __construct(
        public string $uuid,
        /** `StakingPositionState` lists the known values: `CREATED`, `ENABLED`, `UNSTAKING`, `CLOSED`, `FAILED` */
        public string $state,
        /** Net capital placed, as a string */
        public string $amount,
        /** Failure reason when `FAILED` */
        public ?string $error,
        public StakingPositionData $data,
        public int $createdAt,
        public int $updatedAt,
        /** The provider */
        public CoreRef $core,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            Cast::string($data, 'uuid'),
            Cast::string($data, 'state'),
            Cast::string($data, 'amount'),
            Cast::nullableString($data, 'error'),
            StakingPositionData::fromArray(Cast::object($data, 'data')),
            Cast::int($data, 'createdAt'),
            Cast::int($data, 'updatedAt'),
            CoreRef::fromArray(Cast::object($data, 'core')),
        );
    }
}
