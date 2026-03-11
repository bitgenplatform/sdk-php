<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Resources;

use Bitgen\Sdk\HttpClient;

use Bitgen\Sdk\Support\UserRef;

use Bitgen\Sdk\Models\UserFull;
use Bitgen\Sdk\Models\UserListItem;
use Bitgen\Sdk\Models\Wallet;

class WalletResource
{
    public function __construct(private readonly HttpClient $http) {}

    /**
     * @return Wallet[]
     */
    public function list(string|UserFull|UserListItem $user): array
    {
        $data = $this->http->get('/api/v3/wallet/' . rawurlencode(UserRef::resolve($user)));
        return array_map(static fn(array $item) => Wallet::fromArray($item), $data);
    }

    public function get(string|UserFull|UserListItem $user, string $asset): Wallet
    {
        $data = $this->http->get(
            '/api/v3/wallet/' . rawurlencode(UserRef::resolve($user)) . '/' . rawurlencode($asset),
        );
        return Wallet::fromArray($data);
    }
}
