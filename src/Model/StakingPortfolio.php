<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** The EUR balances and curves of a customer's staking (`GET /staking/{user}/portfolio`) */
final readonly class StakingPortfolio
{
    public function __construct(
        public string $uuid,
        public StakingBalances $balances,
        public StakingHistories $histories,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            Cast::string($data, 'uuid'),
            StakingBalances::fromArray(Cast::object($data, 'balances')),
            StakingHistories::fromArray(Cast::object($data, 'histories')),
        );
    }
}
