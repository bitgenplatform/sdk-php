<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** The destination of a withdrawal is a platform (an exchange, a custodian…) — `{ platform }` */
final readonly class TravelRulePlatform extends TravelRule
{
    public function __construct(
        public string $platform,
    ) {
    }

    public function toArray(): array
    {
        return ['platform' => $this->platform];
    }
}
