<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Resources;

use Bitgen\Sdk\HttpClient;
use Bitgen\Sdk\Models\Wallet;

class WalletResource
{
    public function __construct(private readonly HttpClient $http) {}

    /**
     * @return Wallet[]
     */
    public function list(string $user): array
    {
        $data = $this->http->get('/api/v3/wallet/' . rawurlencode($user));

        return array_map(static fn(array $item) => Wallet::fromArray($item), $data);
    }

    public function get(string $user, string $asset): Wallet
    {
        $data = $this->http->get(
            '/api/v3/wallet/' . rawurlencode($user) . '/' . rawurlencode($asset),
        );

        return Wallet::fromArray($data);
    }
}
