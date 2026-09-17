<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** EUR balances of a customer's staking — `{ capital, revenues }` */
final readonly class StakingBalances
{
    public function __construct(
        /** EUR */
        public float $capital,
        /** EUR */
        public float $revenues,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(Cast::float($data, 'capital'), Cast::float($data, 'revenues'));
    }
}
