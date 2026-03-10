<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Models;

readonly class UsersStats
{
    public function __construct(
        public int    $total,
        public int    $remain,
        public int    $registered,
        public string $reset,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            total:      (int)    $data['total'],
            remain:     (int)    $data['remain'],
            registered: (int)    $data['registered'],
            reset:      (string) $data['reset'],
        );
    }
}
