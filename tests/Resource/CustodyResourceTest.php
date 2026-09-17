<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Tests\Resource;

use Bitgen\Sdk\Asset;
use Bitgen\Sdk\Exception\BitgenException;
use Bitgen\Sdk\Http\HttpClient;
use Bitgen\Sdk\Model\AssetRef;
use Bitgen\Sdk\Model\Created;
use Bitgen\Sdk\Model\OrderUser;
use Bitgen\Sdk\Model\TravelRulePerson;
use Bitgen\Sdk\Model\TravelRulePlatform;
use Bitgen\Sdk\Model\WalletState;
use Bitgen\Sdk\Model\WalletType;
use Bitgen\Sdk\Resource\CustodyResource;
use Bitgen\Sdk\Tests\Http\FakeTransport;
use Bitgen\Sdk\Tests\TypeErrors;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

final class CustodyResourceTest extends TestCase
{
    use TypeErrors;

    private const HISTORY = ['d' => [[1700000000, 900.0], [1700003600, 1015.75]], 'w' => [[1699400000, 880.0]], 'm' => [], 'y' => [], 'all' => []];

    /** A realistic `GET /custody/{user}/{asset}` body (contract § 6) — the list answers the same without `history` */
    public const WALLET = [
        'uuid' => 'w-1', 'state' => 'CREATED', 'type' => 'USER', 'address' => '0xabc', 'addressLegacy' => null, 'tag' => null,
        'balance' => '0.500000000000000001', 'history' => self::HISTORY, 'asset' => ['uuid' => 'asset-eth', 'iso' => 'ETH', 'label' => 'Ethereum'],
        'somethingNew' => true,
    ];

    private FakeTransport $transport;
    private CustodyResource $custody;

    protected function setUp(): void
    {
        $this->transport = new FakeTransport();
        $this->custody = new CustodyResource(new HttpClient($this->transport, 'org-uuid', 'k', 'https://api.test', 1000, 'ua'));
    }

    private static function json(mixed $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    public function testWalletsMapTheListWithoutHistory(): void
    {
        $eth = self::WALLET;
        unset($eth['history']);
        $xrp = ['uuid' => 'w-2', 'state' => 'FROZEN', 'type' => 'USER', 'address' => 'rXRP', 'addressLegacy' => 'XLEGACY', 'tag' => '12345', 'balance' => '10', 'asset' => ['uuid' => 'asset-xrp', 'iso' => 'XRP', 'label' => 'Ripple']];
        $this->transport->willAnswer(200, self::json([$eth, $xrp]));
        $wallets = $this->custody->wallets(new Created('c-1'));

        self::assertSame('GET', $this->transport->last()['method']);
        self::assertSame('https://api.test/custody/c-1', $this->transport->last()['url']);
        self::assertNull($this->transport->last()['body']);
        self::assertCount(2, $wallets);
        self::assertSame('w-1', $wallets[0]->uuid);
        self::assertSame(WalletState::CREATED, $wallets[0]->state);
        self::assertSame(WalletType::USER, $wallets[0]->type);
        self::assertSame('0xabc', $wallets[0]->address);
        self::assertNull($wallets[0]->addressLegacy);
        self::assertNull($wallets[0]->tag);
        self::assertSame('0.500000000000000001', $wallets[0]->balance);
        self::assertNull($wallets[0]->history);
        self::assertSame('asset-eth', $wallets[0]->asset->uuid);
        self::assertSame('ETH', $wallets[0]->asset->iso);
        self::assertSame('Ethereum', $wallets[0]->asset->label);
        self::assertSame(WalletState::FROZEN, $wallets[1]->state);
        self::assertSame('XLEGACY', $wallets[1]->addressLegacy);
        self::assertSame('12345', $wallets[1]->tag);
        self::assertSame('10', $wallets[1]->balance);

        // the treasury of the organization, by its uuid (the scope) — an empty list is an empty array
        $this->transport->willAnswer(200, '[]');
        self::assertSame([], $this->custody->wallets('org-uuid'));
        self::assertSame('https://api.test/custody/org-uuid', $this->transport->last()['url']);
    }

    public function testWalletReadsOneAssetWithHistory(): void
    {
        $this->transport->willAnswer(200, self::json(self::WALLET));
        $wallet = $this->custody->wallet('c-1', Asset::ETH);

        self::assertSame('https://api.test/custody/c-1/eth', $this->transport->last()['url']);
        self::assertSame('w-1', $wallet->uuid);
        self::assertNotNull($wallet->history);
        self::assertSame([[1700000000, 900.0], [1700003600, 1015.75]], $wallet->history->d);
        self::assertSame([[1699400000, 880.0]], $wallet->history->w);
        self::assertSame([], $wallet->history->all);

        // a new wallet: the API initializes `history` to `{}` until the curve has been computed
        $this->transport->willAnswer(200, self::json(['history' => []] + self::WALLET));
        self::assertNull($this->custody->wallet('c-1', Asset::ETH)->history);

        $this->custody->wallet('jean@valjean.fr', 'asset-eth');
        self::assertSame('https://api.test/custody/jean%40valjean.fr/asset-eth', $this->transport->last()['url']);
        $this->custody->wallet('c-1', 'BTC');
        self::assertSame('https://api.test/custody/c-1/BTC', $this->transport->last()['url']);   // sent as is: the API normalizes the case
    }

    public function testPortfolioHasItsOwnPathAndTwoShapes(): void
    {
        $this->transport->willAnswer(200, self::json(['uuid' => 'custody-1', 'type' => 'USER', 'history' => self::HISTORY]));
        $portfolio = $this->custody->portfolio('c-1');

        self::assertSame('https://api.test/custody/c-1/portfolio', $this->transport->last()['url']);   // not /custody/c-1/{asset}
        self::assertSame('custody-1', $portfolio->uuid);
        self::assertSame(WalletType::USER, $portfolio->type);
        self::assertSame([[1700000000, 900.0], [1700003600, 1015.75]], $portfolio->history->d);

        // flat `{ history }` at zero while the customer has no custody
        $this->transport->willAnswer(200, self::json(['history' => ['d' => [[1700000000, 0]], 'w' => [], 'm' => [], 'y' => [], 'all' => []]]));
        $empty = $this->custody->portfolio(new Created('c-2'));
        self::assertNull($empty->uuid);
        self::assertNull($empty->type);
        self::assertSame([[1700000000, 0.0]], $empty->history->d);
    }

    public function testWithdrawSendsTheExactBody(): void
    {
        $this->transport->willAnswer(200, '{"transaction":"tx-1"}');
        $withdrawal = $this->custody->withdraw('c-1', Asset::ETH, '0.000000000000000001', '0xdef');
        self::assertSame('tx-1', $withdrawal->transaction);
        self::assertSame('PUT', $this->transport->last()['method']);
        self::assertSame('https://api.test/custody/c-1', $this->transport->last()['url']);
        self::assertSame('{"asset":"eth","amount":"0.000000000000000001","targetAddress":"0xdef"}', $this->transport->last()['body']);

        // every option, a person
        $this->transport->willAnswer(200, '{"transaction":null}');
        $pending = $this->custody->withdraw(new Created('c-1'), Asset::XRP, 10, 'rDEST', targetTag: '12345', idempotencyKey: 'withdraw-42', travelRule: new TravelRulePerson(firstname: 'Jean', lastname: 'Valjean', address: '1 rue de Paris'));
        self::assertNull($pending->transaction);
        self::assertSame(
            '{"asset":"xrp","amount":"10","targetAddress":"rDEST","targetTag":"12345","idempotencyKey":"withdraw-42","travelRule":{"firstname":"Jean","lastname":"Valjean","address":"1 rue de Paris"}}',
            $this->transport->last()['body'],
        );

        // a platform; a person with one field only sends that field
        $this->custody->withdraw('c-1', 'asset-eth', 0.5, '0xdef', travelRule: new TravelRulePlatform('Kraken'));
        self::assertSame('{"asset":"asset-eth","amount":"0.5","targetAddress":"0xdef","travelRule":{"platform":"Kraken"}}', $this->transport->last()['body']);
        $this->custody->withdraw('c-1', Asset::ETH, '1', '0xdef', travelRule: new TravelRulePerson(lastname: 'Valjean'));
        self::assertSame('{"asset":"eth","amount":"1","targetAddress":"0xdef","travelRule":{"lastname":"Valjean"}}', $this->transport->last()['body']);
    }

    public function testApiErrorsBecomeBitgenExceptions(): void
    {
        $cases = [
            [403, 'kyc_not_validated', fn () => $this->custody->wallet('c-1', Asset::ETH)],
            [403, 'wallet_frozen', fn () => $this->custody->withdraw('c-1', Asset::ETH, '1', '0xdef')],
            [412, 'custody_not_enabled', fn () => $this->custody->wallet('c-1', Asset::ETH)],
            [400, 'invalid_amount', fn () => $this->custody->withdraw('c-1', Asset::ETH, '0', '0xdef')],
            [416, 'withdraw_below_minimum', fn () => $this->custody->withdraw('c-1', Asset::ETH, '0.0001', '0xdef')],
            [422, 'withdraw_target_invalid', fn () => $this->custody->withdraw('c-1', Asset::ETH, '1', 'nope')],
            [415, 'custody_portfolio_treasury_unsupported', fn () => $this->custody->portfolio('org-uuid')],
            [403, 'org_forbidden', fn () => $this->custody->wallets('c-1')],
            [403, 'forbidden_permission', fn () => $this->custody->wallets('c-1')],
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

    /** @return iterable<string, array{string}> */
    public static function notAListOfObjects(): iterable
    {
        yield 'an object' => ['{"uuid":"w-1"}'];
        yield 'a list of strings' => ['["w-1"]'];
        yield 'a string' => ['"text"'];
    }

    #[DataProvider('notAListOfObjects')]
    public function testAListThatIsNotAListOfObjectsIsAContractViolation(string $body): void
    {
        $this->transport->willAnswer(200, $body);
        $this->expectException(UnexpectedValueException::class);
        $this->custody->wallets('c-1');
    }

    public function testInvalidArgumentsAreRefusedBeforeAnyRequest(): void
    {
        $calls = [
            fn () => $this->custody->withdraw('c-1', Asset::ETH, -1, '0xdef'),
            fn () => $this->custody->withdraw('c-1', Asset::ETH, '', '0xdef'),
            fn () => $this->custody->withdraw('c-1', Asset::ETH, 1e-8, '0xdef'),
            fn () => $this->custody->withdraw('', Asset::ETH, '1', '0xdef'),
            fn () => $this->custody->wallet('c-1', '..'),
            fn () => $this->custody->wallets(new Created('')),
            fn () => new TravelRulePerson(),   // an empty person is meaningless
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

    public function testModelsAreAcceptedForTheCustomerAndTheAsset(): void
    {
        $this->transport->willAnswer(200, self::json(self::WALLET));
        $wallet = $this->custody->wallet(new OrderUser('c-1', 'jean@valjean.fr'), new AssetRef('asset-eth', 'ETH', 'Ethereum'));
        self::assertSame('https://api.test/custody/c-1/asset-eth', $this->transport->last()['url']);   // the uuid of the model, never its iso
        $this->transport->willAnswer(200, '{"transaction":"tx-1"}');
        $this->custody->withdraw(new Created('c-1'), $wallet->asset, '1', '0xdef');
        self::assertSame('{"asset":"asset-eth","amount":"1","targetAddress":"0xdef"}', $this->transport->last()['body']);

        // a wrong model is a type error at the call: nothing is sent
        $sent = count($this->transport->requests);
        self::assertTypeError(fn () => $this->custody->wallets(new \stdClass())); // @phpstan-ignore argument.type
        self::assertTypeError(fn () => $this->custody->wallet('c-1', new Created('asset-eth'))); // @phpstan-ignore argument.type
        self::assertCount($sent, $this->transport->requests);
    }
}
