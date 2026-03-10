<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Resources;

use Bitgen\Sdk\HttpClient;
use Bitgen\Sdk\Models\Asset;

class AssetResource
{
    public function __construct(private readonly HttpClient $http) {}

    public function get(string $iso): Asset
    {
        $data = $this->http->get('/api/v3/asset/' . rawurlencode($iso));

        return Asset::fromArray($data);
    }

    /**
     * @return Asset[]
     */
    public function list(): array
    {
        $data = $this->http->get('/api/v3/assets');

        return array_map(static fn(array $item) => Asset::fromArray($item), $data);
    }
}
