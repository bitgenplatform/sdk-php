<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Resource;

use Bitgen\Sdk\Exception\BitgenException;
use Bitgen\Sdk\Http\HttpClient;
use Bitgen\Sdk\Model\Account;
use Bitgen\Sdk\Model\Asset;
use Bitgen\Sdk\Model\AssetRef;
use Bitgen\Sdk\Model\Cast;
use Bitgen\Sdk\Model\Created;
use Bitgen\Sdk\Model\Customer;
use Bitgen\Sdk\Model\Order;
use Bitgen\Sdk\Model\OrderCreated;
use Bitgen\Sdk\Model\OrderSide;
use Bitgen\Sdk\Model\OrderUser;
use Bitgen\Sdk\Model\TradingDirection;
use Bitgen\Sdk\Model\UserSummary;
use Bitgen\Sdk\Page;
use Bitgen\Sdk\Support\Amount;
use Bitgen\Sdk\Support\AssetId;
use Bitgen\Sdk\Support\Enum;
use Bitgen\Sdk\Support\Path;
use Bitgen\Sdk\Support\UserId;
use InvalidArgumentException;

/**
 * `/trading` — buy or sell crypto for a customer (`$client->trading`). The customer is designated by uuid or by
 * a model carrying their uuid — no email here — and must be an `ENABLED` member of the organization.
 *
 * @phpstan-import-type UserRef from UserId
 */
class TradingResource
{
    public function __construct(private readonly HttpClient $http)
    {
    }

    /**
     * Buy crypto with EUR from the customer's bank account
     *
     * @param UserRef               $user
     * @param string|Asset|AssetRef $asset     uuid or ISO code (`Asset::ETH`), or a model (its uuid is sent) — must be `AVAILABLE`
     * @param string|int|float      $amount    EUR to spend, 2 decimals max, at least `MIN_BUY_CRYPTO`
     * @param string|null           $reference idempotency key per (customer, side, reference): replaying it returns the existing order
     *
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     * @throws InvalidArgumentException an argument is invalid — nothing was sent
     */
    public function buy(string|Created|Customer|Account|UserSummary|OrderUser $user, string|Asset|AssetRef $asset, string|int|float $amount, ?string $reference = null): OrderCreated
    {
        return $this->order($user, $asset, $amount, OrderSide::BUY, $reference);
    }

    /**
     * Sell crypto from the customer's custody wallet
     *
     * @param UserRef               $user
     * @param string|Asset|AssetRef $asset     uuid or ISO code (`Asset::ETH`), or a model (its uuid is sent) — must be `AVAILABLE`
     * @param string|int|float      $amount    the crypto quantity to sell, as a string, at most `baseUnit` decimals; its EUR value must reach `MIN_SELL_CRYPTO`
     * @param string|null           $reference idempotency key per (customer, side, reference): replaying it returns the existing order
     *
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     * @throws InvalidArgumentException an argument is invalid — nothing was sent
     */
    public function sell(string|Created|Customer|Account|UserSummary|OrderUser $user, string|Asset|AssetRef $asset, string|int|float $amount, ?string $reference = null): OrderCreated
    {
        return $this->order($user, $asset, $amount, OrderSide::SELL, $reference);
    }

    /**
     * One order by its uuid (the `tunnel` returned by `buy` / `sell`) or by model
     *
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     * @throws InvalidArgumentException an argument is invalid — nothing was sent
     */
    public function get(string|Order $order): Order
    {
        return Order::fromArray(Cast::answer($this->http->get('/trading/' . Path::segment($order instanceof Order ? $order->uuid : $order, 'order'))));
    }

    /**
     * The orders of the organization, optionally filtered by customer, side and asset
     *
     * @param UserRef|null               $user      only the orders of this customer (unknown → `404 unknown_user`)
     * @param string|null                $direction `buy` or `sell` — a `TradingDirection` constant; absent, both
     * @param string|Asset|AssetRef|null $asset     only this asset (ISO code, uuid or model)
     *
     * @return Page<Order>
     *
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     * @throws InvalidArgumentException an argument is invalid — nothing was sent
     */
    public function list(string|Created|Customer|Account|UserSummary|OrderUser|null $user = null, ?string $direction = null, string|Asset|AssetRef|null $asset = null, ?int $offset = null, ?int $limit = null): Page
    {
        $query = [
            'user' => $user === null ? null : UserId::resolve($user),
            'direction' => $direction === null ? null : Enum::ensure($direction, TradingDirection::VALUES, 'direction'),
            'asset' => $asset === null ? null : AssetId::resolve($asset),
            'offset' => $offset,
            'limit' => $limit,
        ];

        return Page::fromArray(Cast::answer($this->http->get('/trading/orders', $query)), Order::fromArray(...));
    }

    /**
     * `POST /trading` — the body of the contract, `mode` set by the caller
     *
     * @param UserRef $user
     * @param string  $mode `OrderSide::BUY` or `OrderSide::SELL`
     *
     * @throws BitgenException
     * @throws InvalidArgumentException
     */
    private function order(string|Created|Customer|Account|UserSummary|OrderUser $user, string|Asset|AssetRef $asset, string|int|float $amount, string $mode, ?string $reference): OrderCreated
    {
        $body = array_filter([
            'user' => UserId::resolve($user),
            'asset' => AssetId::resolve($asset),
            'amount' => Amount::normalize($amount),
            'mode' => $mode,
            'reference' => $reference,
        ], static fn (mixed $value): bool => $value !== null);

        return OrderCreated::fromArray(Cast::answer($this->http->post('/trading', $body)));
    }
}
