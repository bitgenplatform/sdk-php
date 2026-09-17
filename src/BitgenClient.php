<?php

declare(strict_types=1);

namespace Bitgen\Sdk;

use Bitgen\Sdk\Http\BaseUrl;
use Bitgen\Sdk\Http\CurlTransport;
use Bitgen\Sdk\Http\HttpClient;
use Bitgen\Sdk\Http\Timeout;
use Bitgen\Sdk\Resource\ApikeysResource;
use Bitgen\Sdk\Resource\AssetResource;
use Bitgen\Sdk\Resource\BankResource;
use Bitgen\Sdk\Resource\CoreResource;
use Bitgen\Sdk\Resource\CustodyResource;
use Bitgen\Sdk\Resource\CustomerResource;
use Bitgen\Sdk\Resource\StakingResource;
use Bitgen\Sdk\Resource\TradingResource;
use Bitgen\Sdk\Resource\TransactionResource;
use Bitgen\Sdk\Resource\WebhooksResource;
use InvalidArgumentException;

/**
 * Entry point of the SDK: one instance per API key, the resources hang off it (`$client->customer`, `$client->bank`, `$client->custody`,
 * `$client->trading`, `$client->transaction`, `$client->staking`, `$client->core`, `$client->webhooks`, `$client->apikeys`, `$client->asset`).
 * An invalid configuration throws an InvalidArgumentException here, before any request is sent.
 *
 * ```php
 * $client = new BitgenClient(scope: 'YOUR_SCOPE_UUID', apiKey: 'YOUR_API_KEY', env: Env::SANDBOX);
 * ```
 */
class BitgenClient
{
    /** The customers of the organization: creation, listing, accounts */
    public readonly CustomerResource $customer;
    /** The EUR account of each customer */
    public readonly BankResource $bank;
    /** The crypto wallets of each customer, per asset: deposit addresses, balances, on-chain withdrawals */
    public readonly CustodyResource $custody;
    /** Purchases and sales of crypto for a customer, through the exchange of the platform */
    public readonly TradingResource $trading;
    /** The journal of the fiat and crypto movements of the organization, read-only */
    public readonly TransactionResource $transaction;
    /** Staking positions of the customers: providers, movements, rewards, portfolio */
    public readonly StakingResource $staking;
    /** The catalogue of the connectors of the platform, read-only */
    public readonly CoreResource $core;
    /** The deliveries of the events of the organization to an endpoint: setup, subscriptions, logs, `verify()` */
    public readonly WebhooksResource $webhooks;
    /** The keys of the organization and the journal of their calls, read-only */
    public readonly ApikeysResource $apikeys;
    /** The catalogue of assets, tickers and EUR price histories */
    public readonly AssetResource $asset;

    /**
     * @param string      $scope   uuid of the organization that owns the key (`BITGEN-Scope` header)
     * @param string      $apiKey  the raw key (`Api-key` header)
     * @param string      $env     target environment, one of the `Env` constants — `Env::PRODUCTION` by default
     * @param string|null $host    custom hostname (bare: no scheme, port or path), used instead of `$env`
     * @param int|null    $port    port — with `$host` (default 80) or `Env::LOCALHOST` (default 3002)
     * @param bool        $isSsl   `https` (default) or `http`, with `$host`
     * @param int|float   $timeout request timeout in seconds, `30` by default, `0` = none
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        string $scope,
        string $apiKey,
        string $env = Env::PRODUCTION,
        ?string $host = null,
        ?int $port = null,
        bool $isSsl = true,
        int|float $timeout = Timeout::DEFAULT,
    ) {
        self::requireHeaderValue($scope, 'scope');
        self::requireHeaderValue($apiKey, 'apiKey');

        $http = new HttpClient(
            new CurlTransport(),
            $scope,
            $apiKey,
            BaseUrl::resolve($env, $host, $port, $isSsl),
            Timeout::toMilliseconds($timeout),
            'bitgen-sdk-php/' . Version::VERSION,
        );

        $this->customer = new CustomerResource($http);
        $this->bank = new BankResource($http);
        $this->custody = new CustodyResource($http);
        $this->trading = new TradingResource($http);
        $this->transaction = new TransactionResource($http);
        $this->core = new CoreResource($http);
        $this->staking = new StakingResource($http, $this->core);
        $this->webhooks = new WebhooksResource($http);
        $this->apikeys = new ApikeysResource($http);
        $this->asset = new AssetResource($http);
    }

    /** Non-empty printable ASCII (a header value) — the value itself is never echoed */
    private static function requireHeaderValue(string $value, string $name): void
    {
        if ($value === '') {
            throw new InvalidArgumentException(sprintf('%s must be a non-empty string', $name));
        }
        if (preg_match('/^[\x20-\x7E]+\z/', $value) !== 1) {
            throw new InvalidArgumentException(sprintf('%s contains invalid characters (printable ASCII expected)', $name));
        }
    }
}
