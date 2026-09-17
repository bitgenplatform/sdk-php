<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Tests\Resource;

use Bitgen\Sdk\Asset;
use Bitgen\Sdk\Exception\BitgenException;
use Bitgen\Sdk\Http\HttpClient;
use Bitgen\Sdk\Model\Created;
use Bitgen\Sdk\Model\Customer;
use Bitgen\Sdk\Model\OrderSide;
use Bitgen\Sdk\Model\OrderState;
use Bitgen\Sdk\Model\TradingDirection;
use Bitgen\Sdk\Resource\TradingResource;
use Bitgen\Sdk\Tests\Http\FakeTransport;
use Bitgen\Sdk\Tests\TypeErrors;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TradingResourceTest extends TestCase
{
    use TypeErrors;

    /** A realistic `GET /trading/{order}` body (contract § 7) */
    public const ORDER = [
        'uuid' => 'o-1', 'state' => 'DONE', 'side' => 'BUY', 'amount' => '25.00', 'reference' => 'order-42',
        'received' => 0.0123, 'executedPrice' => 2031.5, 'fee' => 0.25, 'completedAt' => 1700003600, 'createdAt' => 1700000000,
        'user' => ['uuid' => 'c-1', 'login' => 'jean@valjean.fr'], 'organization' => ['uuid' => 'org-uuid', 'name' => 'ACME'],
        'asset' => ['uuid' => 'asset-eth', 'iso' => 'ETH', 'label' => 'Ethereum'], 'somethingNew' => true,
    ];

    private FakeTransport $transport;
    private TradingResource $trading;

    protected function setUp(): void
    {
        $this->transport = new FakeTransport();
        $this->trading = new TradingResource(new HttpClient($this->transport, 'org-uuid', 'k', 'https://api.test', 1000, 'ua'));
    }

    private static function json(mixed $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    public function testBuyAndSellSendTheExactBody(): void
    {
        $this->transport->willAnswer(201, '{"tunnel":"o-1","state":"REGISTERED"}');
        $created = $this->trading->buy(new Created('c-1'), Asset::ETH, '25.00', 'order-42');
        self::assertSame('o-1', $created->tunnel);
        self::assertSame(OrderState::REGISTERED, $created->state);
        self::assertSame('POST', $this->transport->last()['method']);
        self::assertSame('https://api.test/trading', $this->transport->last()['url']);
        self::assertSame('{"user":"c-1","asset":"eth","amount":"25.00","mode":"BUY","reference":"order-42"}', $this->transport->last()['body']);

        // no reference: the key is absent, not null; an int amount travels as a string
        $this->trading->buy('c-1', 'asset-eth', 25);
        self::assertSame('{"user":"c-1","asset":"asset-eth","amount":"25","mode":"BUY"}', $this->transport->last()['body']);

        $this->transport->willAnswer(201, '{"tunnel":"o-2","state":"REGISTERED"}');
        $sale = $this->trading->sell('c-1', Asset::ETH, '0.01');
        self::assertSame('o-2', $sale->tunnel);
        self::assertSame('{"user":"c-1","asset":"eth","amount":"0.01","mode":"SELL"}', $this->transport->last()['body']);
        $this->trading->sell('c-1', Asset::BTC, 0.5, reference: 'sale-1');
        self::assertSame('{"user":"c-1","asset":"btc","amount":"0.5","mode":"SELL","reference":"sale-1"}', $this->transport->last()['body']);
    }

    public function testGetMapsTheOrder(): void
    {
        $this->transport->willAnswer(200, self::json(self::ORDER));
        $order = $this->trading->get('o-1');

        self::assertSame('GET', $this->transport->last()['method']);
        self::assertSame('https://api.test/trading/o-1', $this->transport->last()['url']);
        self::assertSame('o-1', $order->uuid);
        self::assertSame(OrderState::DONE, $order->state);
        self::assertSame(OrderSide::BUY, $order->side);
        self::assertSame('25.00', $order->amount);
        self::assertSame('order-42', $order->reference);
        self::assertSame(0.0123, $order->received);
        self::assertSame(2031.5, $order->executedPrice);
        self::assertSame(0.25, $order->fee);
        self::assertSame(1700003600, $order->completedAt);
        self::assertSame(1700000000, $order->createdAt);
        self::assertSame('c-1', $order->user->uuid);
        self::assertSame('jean@valjean.fr', $order->user->login);
        self::assertSame('org-uuid', $order->organization->uuid);
        self::assertSame('ACME', $order->organization->name);
        self::assertSame('asset-eth', $order->asset->uuid);
        self::assertSame('ETH', $order->asset->iso);
        self::assertSame('Ethereum', $order->asset->label);

        // a fresh order: nothing received yet
        $this->transport->willAnswer(200, self::json(['uuid' => 'o-2', 'state' => 'REGISTERED', 'side' => 'SELL', 'amount' => '0.01', 'reference' => null, 'received' => null, 'executedPrice' => null, 'fee' => null, 'completedAt' => null, 'createdAt' => 1700000000, 'user' => ['uuid' => 'c-1', 'login' => 'jean@valjean.fr'], 'organization' => ['uuid' => 'org-uuid', 'name' => 'ACME'], 'asset' => ['uuid' => 'asset-eth', 'iso' => 'ETH', 'label' => 'Ethereum']]));
        $fresh = $this->trading->get('o-2');
        self::assertSame(OrderSide::SELL, $fresh->side);
        self::assertNull($fresh->reference);
        self::assertNull($fresh->received);
        self::assertNull($fresh->executedPrice);
        self::assertNull($fresh->fee);
        self::assertNull($fresh->completedAt);

        // the order returned by buy is readable as is, through its tunnel
        $this->trading->get('o-1');
        self::assertSame('https://api.test/trading/o-1', $this->transport->last()['url']);
    }

    public function testListSendsTheExactQueryAndMapsOrders(): void
    {
        $this->transport->willAnswer(200, self::json(['count' => 1, 'items' => [self::ORDER]]));
        $page = $this->trading->list(user: new Created('c-1'), direction: TradingDirection::SELL, asset: Asset::ETH, offset: 0, limit: 50);

        self::assertSame('https://api.test/trading/orders?user=c-1&direction=sell&asset=eth&offset=0&limit=50', $this->transport->last()['url']);
        self::assertSame(1, $page->count);
        self::assertSame('o-1', $page->items[0]->uuid);
        self::assertSame(OrderState::DONE, $page->items[0]->state);

        $this->trading->list();
        self::assertSame('https://api.test/trading/orders', $this->transport->last()['url']);
        $this->trading->list(user: 'c-1', direction: TradingDirection::BUY, asset: 'ETH');   // an ISO code in upper case is sent as is
        self::assertSame('https://api.test/trading/orders?user=c-1&direction=buy&asset=ETH', $this->transport->last()['url']);
        $this->trading->list(user: $page->items[0]->user);   // the user of an order carries a uuid
        self::assertSame('https://api.test/trading/orders?user=c-1', $this->transport->last()['url']);
    }

    public function testApiErrorsBecomeBitgenExceptions(): void
    {
        $cases = [
            [403, 'kyc_not_validated', fn () => $this->trading->buy('c-1', Asset::ETH, '25.00')],
            [404, 'unknown_order', fn () => $this->trading->get('o-x')],
            [412, 'price_unavailable', fn () => $this->trading->buy('c-1', Asset::ETH, '25.00')],
            [416, 'requested_amount_error', fn () => $this->trading->sell('c-1', Asset::ETH, '100')],
            [422, 'invalid_asset', fn () => $this->trading->buy('c-1', 'xyz', '25.00')],
            [422, 'asset_not_supported', fn () => $this->trading->sell('c-1', 'xyz', '1')],   // a custody error surfacing through a sale
            [403, 'user_not_in_scope', fn () => $this->trading->buy('c-1', Asset::ETH, '25.00')],
            [404, 'unknown_user', fn () => $this->trading->list(user: 'c-x')],
            [403, 'forbidden_permission', fn () => $this->trading->list()],
        ];
        foreach ($cases as [$status, $code, $call]) {
            $this->transport->willAnswer($status, self::json(['error' => true, 'message' => $code, 'code' => $status]));
            try {
                $call();
                self::fail('expected a BitgenException');
            } catch (BitgenException $e) {
                self::assertSame($status, $e->status);
                self::assertSame($code, $e->errorCode);
            }
        }
    }

    public function testInvalidArgumentsAreRefusedBeforeAnyRequest(): void
    {
        try {
            $this->trading->list(direction: 'BUY');   // the filter is lowercase: the API would silently ignore it
            self::fail('expected an InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            self::assertSame('direction must be buy or sell', $e->getMessage());
        }
        $calls = [
            fn () => $this->trading->buy('c-1', Asset::ETH, -1),
            fn () => $this->trading->buy('c-1', Asset::ETH, ''),
            fn () => $this->trading->sell('c-1', Asset::ETH, 1e-8),
            fn () => $this->trading->buy('', Asset::ETH, '25.00'),
            fn () => $this->trading->buy(new Created(''), Asset::ETH, '25.00'),
            fn () => $this->trading->get(''),
            fn () => $this->trading->get('..'),
            fn () => $this->trading->list(user: ''),
        ];
        foreach ($calls as $call) {
            try {
                $call();
                self::fail('expected an InvalidArgumentException');
            } catch (InvalidArgumentException) {
            }
        }
        self::assertSame([], $this->transport->requests);
    }

    public function testModelsAreAcceptedForTheCustomerTheAssetAndTheOrder(): void
    {
        $this->transport->willAnswer(200, self::json(self::ORDER));
        $order = $this->trading->get('o-1');
        $this->transport->willAnswer(200, self::json(self::ORDER));
        $this->trading->get($order);
        self::assertSame('https://api.test/trading/o-1', $this->transport->last()['url']);

        $this->transport->willAnswer(201, '{"tunnel":"o-3","state":"REGISTERED"}');
        $this->trading->buy($order->user, $order->asset, '25.00');   // the user and the asset of an order, by model: their uuids are sent
        self::assertSame('{"user":"c-1","asset":"asset-eth","amount":"25.00","mode":"BUY"}', $this->transport->last()['body']);
        $this->trading->list(user: Customer::fromArray(CustomerResourceTest::CUSTOMER), asset: $order->asset);
        self::assertSame('https://api.test/trading/orders?user=c-1&asset=asset-eth', $this->transport->last()['url']);

        $sent = count($this->transport->requests);
        self::assertTypeError(fn () => $this->trading->get($order->user)); // @phpstan-ignore argument.type
        self::assertTypeError(fn () => $this->trading->buy($order, Asset::ETH, '25.00')); // @phpstan-ignore argument.type
        self::assertCount($sent, $this->transport->requests);
    }
}
