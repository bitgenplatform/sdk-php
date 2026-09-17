<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/**
 * A staking request — stake, unstake, withdraw or claim rewards — attached to a position. Its `uuid` is what
 * `stake` returns and `get` / `list` / `movements` handle (`GET /staking/{movement}`).
 */
final readonly class StakingMovement
{
    public function __construct(
        public string $uuid,
        /** `StakingMovementState` lists the known values: `REQUESTED`, `PENDING`, `COMPLETED`, `FAILED`, `CANCELED` */
        public string $state,
        /** `StakingMovementKind` lists the known values: `STAKE`, `UNSTAKE`, `WITHDRAW`, `REWARD` */
        public string $kind,
        /** The `name` of the staking connector (`figment_sol`) */
        public string $provider,
        /** The quantity of the movement, as a string */
        public string $amount,
        public int $createdAt,
        public int $updatedAt,
        /** The position — `$staking->uuid` is what `rewards` / `unstake` take */
        public StakingPosition $staking,
        /** The customer */
        public UserSummary $owner,
        public AssetRef $asset,
        public ?OrganizationSummary $organization,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $organization = Cast::nullableObject($data, 'organization');

        return new self(
            Cast::string($data, 'uuid'),
            Cast::string($data, 'state'),
            Cast::string($data, 'kind'),
            Cast::string($data, 'provider'),
            Cast::string($data, 'amount'),
            Cast::int($data, 'createdAt'),
            Cast::int($data, 'updatedAt'),
            StakingPosition::fromArray(Cast::object($data, 'staking')),
            UserSummary::fromArray(Cast::object($data, 'owner')),
            AssetRef::fromArray(Cast::object($data, 'asset')),
            $organization === null ? null : OrganizationSummary::fromArray($organization),
        );
    }
}
