<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** EUR pending on an account: incoming not credited yet, outgoing not confirmed yet */
final readonly class BankPending
{
    public function __construct(
        public float $in,
        public float $out,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(Cast::float($data, 'in'), Cast::float($data, 'out'));
    }
}
