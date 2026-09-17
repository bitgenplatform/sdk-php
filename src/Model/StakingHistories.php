<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** EUR curves of a customer's staking — `{ capital, revenues }`, two `History` */
final readonly class StakingHistories
{
    public function __construct(
        public History $capital,
        public History $revenues,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(History::fromArray(Cast::object($data, 'capital')), History::fromArray(Cast::object($data, 'revenues')));
    }
}
