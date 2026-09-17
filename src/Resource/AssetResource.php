<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Resource;

use Bitgen\Sdk\Exception\BitgenException;
use Bitgen\Sdk\Http\HttpClient;
use Bitgen\Sdk\Model\Asset;
use Bitgen\Sdk\Model\AssetRef;
use Bitgen\Sdk\Model\AssetTickerDetail;
use Bitgen\Sdk\Model\AssetTickerItem;
use Bitgen\Sdk\Model\Cast;
use Bitgen\Sdk\Page;
use Bitgen\Sdk\Support\AssetId;
use Bitgen\Sdk\Support\Path;
use InvalidArgumentException;

/**
 * `/asset`, `/ticker` — the catalogue of assets, their tickers and EUR price histories (`$client->asset`).
 */
class AssetResource
{
    public function __construct(private readonly HttpClient $http)
    {
    }

    /**
     * Every asset of the platform
     *
     * @return Page<Asset>
     *
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     */
    public function list(): Page
    {
        return Page::fromArray(Cast::answer($this->http->get('/asset')), Asset::fromArray(...));
    }

    /**
     * One asset, by uuid or ISO code (any case, sent as is — `Asset::ETH`, `'eth'`) or by model (its uuid is sent)
     *
     * @param string|Asset|AssetRef $asset
     *
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     * @throws InvalidArgumentException an argument is invalid — nothing was sent
     */
    public function get(string|Asset|AssetRef $asset): Asset
    {
        return Asset::fromArray(Cast::answer($this->http->get('/asset/' . Path::segment(AssetId::resolve($asset), 'asset'))));
    }

    /**
     * The ticker of every asset
     *
     * @return Page<AssetTickerItem>
     *
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     */
    public function tickers(): Page
    {
        return Page::fromArray(Cast::answer($this->http->get('/ticker')), AssetTickerItem::fromArray(...));
    }

    /**
     * The ticker and the EUR price history of one asset, by ISO code
     *
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     * @throws InvalidArgumentException an argument is invalid — nothing was sent
     */
    public function ticker(string $iso): AssetTickerDetail
    {
        return AssetTickerDetail::fromArray(Cast::answer($this->http->get('/ticker/' . Path::segment($iso, 'iso'))));
    }
}
