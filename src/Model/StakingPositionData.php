<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** What the provider reports on a position — `{ rewards?, lastRewardAt? }` */
final readonly class StakingPositionData
{
    public function __construct(
        /** Rewards accrued and available, in asset units, as a string — when available */
        public ?string $rewards,
        /** Epoch seconds of the last daily accrual — when available */
        public ?int $lastRewardAt,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(Cast::nullableString($data, 'rewards'), Cast::nullableInt($data, 'lastRewardAt'));
    }
}
