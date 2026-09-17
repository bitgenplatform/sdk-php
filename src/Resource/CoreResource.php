<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Resource;

use Bitgen\Sdk\Exception\BitgenException;
use Bitgen\Sdk\Http\HttpClient;
use Bitgen\Sdk\Model\Asset;
use Bitgen\Sdk\Model\AssetRef;
use Bitgen\Sdk\Model\Cast;
use Bitgen\Sdk\Model\Core;
use Bitgen\Sdk\Model\CoreState;
use Bitgen\Sdk\Model\CoreType;
use Bitgen\Sdk\Page;
use Bitgen\Sdk\Support\AssetId;
use Bitgen\Sdk\Support\Enum;
use Bitgen\Sdk\Support\Path;
use InvalidArgumentException;

/**
 * `/applications/core` — the catalogue of the connectors of the platform, read-only (`$client->core`).
 */
class CoreResource
{
    public function __construct(private readonly HttpClient $http)
    {
    }

    /**
     * The connectors matching the filters — not paginated: `count` is everything that matches
     *
     * @param string|null                $type  `IDENTITY`, `AML`, `TRADING`, `CUSTODY`, `STAKING` or `RAMP` — a `CoreType` constant
     * @param string|Asset|AssetRef|null $asset only the connectors attached to this asset (the `STAKING` ones): uuid, ISO code or model
     * @param string|null                $state `ENABLED` or `DISABLED` — a `CoreState` constant
     *
     * @return Page<Core>
     *
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     * @throws InvalidArgumentException an argument is invalid — nothing was sent
     */
    public function list(?string $type = null, string|Asset|AssetRef|null $asset = null, ?string $state = null): Page
    {
        $query = [
            'type' => $type === null ? null : Enum::ensure($type, CoreType::VALUES, 'type'),
            'asset' => $asset === null ? null : AssetId::resolve($asset),
            'state' => $state === null ? null : Enum::ensure($state, CoreState::VALUES, 'state'),
        ];

        return Page::fromArray(Cast::answer($this->http->get('/applications/core', $query)), Core::fromArray(...));
    }

    /**
     * One connector, by uuid or by model
     *
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     * @throws InvalidArgumentException an argument is invalid — nothing was sent
     */
    public function get(string|Core $core): Core
    {
        return Core::fromArray(Cast::answer($this->http->get('/applications/core/' . Path::segment($core instanceof Core ? $core->uuid : $core, 'core'))));
    }
}
