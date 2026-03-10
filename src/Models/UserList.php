<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Models;

readonly class UserList
{
    /**
     * @param UserListItem[] $items
     */
    public function __construct(
        public UserListMeta $meta,
        public array        $items,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            meta:  UserListMeta::fromArray($data['meta']),
            items: array_map(
                static fn(array $item) => UserListItem::fromArray($item),
                $data['items'],
            ),
        );
    }
}
