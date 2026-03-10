<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Models;

readonly class UserListMeta
{
    public function __construct(
        public int $offset,
        public int $limit,
        public int $total,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            offset: (int) $data['offset'],
            limit:  (int) $data['limit'],
            total:  (int) $data['total'],
        );
    }
}
