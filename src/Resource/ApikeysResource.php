<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Resource;

use Bitgen\Sdk\Exception\BitgenException;
use Bitgen\Sdk\Http\HttpClient;
use Bitgen\Sdk\Model\Apikey;
use Bitgen\Sdk\Model\ApikeyLog;
use Bitgen\Sdk\Model\Cast;
use Bitgen\Sdk\Page;
use Bitgen\Sdk\Support\Path;
use InvalidArgumentException;

/**
 * `/organization/{organization}/apikeys` — the keys of the organization and the journal of their calls, read-only
 * (`$client->apikeys`): a key cannot be created through the API, and the SDK does not revoke. `{organization}` is always the key's scope.
 */
class ApikeysResource
{
    public function __construct(private readonly HttpClient $http)
    {
    }

    /**
     * The keys of the organization — the `REVOKED` ones with `$includeRevoked`
     *
     * @return Page<Apikey>
     *
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     * @throws InvalidArgumentException an argument is invalid — nothing was sent
     */
    public function list(?bool $includeRevoked = null, ?int $offset = null, ?int $limit = null): Page
    {
        return Page::fromArray(
            Cast::answer($this->http->get('/organization/' . $this->organization() . '/apikeys', ['includeRevoked' => $includeRevoked, 'offset' => $offset, 'limit' => $limit])),
            Apikey::fromArray(...),
        );
    }

    /**
     * One key, by uuid or by model
     *
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     * @throws InvalidArgumentException an argument is invalid — nothing was sent
     */
    public function get(string|Apikey $apikey): Apikey
    {
        return Apikey::fromArray(Cast::answer($this->http->get('/organization/' . $this->organization() . '/apikeys/' . self::apikey($apikey))));
    }

    /**
     * The calls made with a key: path, inputs (personal data masked), status, error
     *
     * @return Page<ApikeyLog>
     *
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     * @throws InvalidArgumentException an argument is invalid — nothing was sent
     */
    public function logs(string|Apikey $apikey, ?int $offset = null, ?int $limit = null): Page
    {
        return Page::fromArray(
            Cast::answer($this->http->get('/organization/' . $this->organization() . '/apikeys/' . self::apikey($apikey) . '/logs', ['offset' => $offset, 'limit' => $limit])),
            ApikeyLog::fromArray(...),
        );
    }

    /** The scope of the key, as the `{organization}` path segment */
    private function organization(): string
    {
        return Path::segment($this->http->scope, 'scope');
    }

    private static function apikey(string|Apikey $apikey): string
    {
        return Path::segment($apikey instanceof Apikey ? $apikey->uuid : $apikey, 'apikey');
    }
}
