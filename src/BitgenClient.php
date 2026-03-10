<?php

declare(strict_types=1);

namespace Bitgen\Sdk;

use Bitgen\Sdk\Resources\AssetResource;
use Bitgen\Sdk\Resources\BankResource;
use Bitgen\Sdk\Resources\OrderResource;
use Bitgen\Sdk\Resources\StatsResource;
use Bitgen\Sdk\Resources\TransactionResource;
use Bitgen\Sdk\Resources\UserResource;
use Bitgen\Sdk\Resources\WalletResource;

class BitgenClient
{
    public readonly AssetResource       $asset;
    public readonly BankResource        $bank;
    public readonly OrderResource       $order;
    public readonly TransactionResource $transaction;
    public readonly UserResource        $user;
    public readonly WalletResource      $wallet;
    public readonly StatsResource       $stats;

    /**
     * @param array{
     *     scope: string,
     *     apiKey: string,
     *     env?: 'sandbox'|'production'|'localhost',
     *     host?: string,
     *     port?: int,
     * } $config
     */
    public function __construct(array $config)
    {
        $cfg  = new Config(
            scope:  $config['scope'],
            apiKey: $config['apiKey'],
            env:    $config['env']  ?? Config::ENV_SANDBOX,
            host:   $config['host'] ?? null,
            port:   $config['port'] ?? null,
        );
        $http = new HttpClient($cfg);

        $this->asset       = new AssetResource($http);
        $this->bank        = new BankResource($http);
        $this->order       = new OrderResource($http);
        $this->transaction = new TransactionResource($http);
        $this->user        = new UserResource($http);
        $this->wallet      = new WalletResource($http);
        $this->stats       = new StatsResource($http);
    }
}
