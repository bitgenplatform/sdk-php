<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Tests\Resource;

use Bitgen\Sdk\Asset;
use Bitgen\Sdk\Exception\BitgenException;
use Bitgen\Sdk\Http\HttpClient;
use Bitgen\Sdk\Model\AssetRef;
use Bitgen\Sdk\Model\AssetState;
use Bitgen\Sdk\Resource\AssetResource;
use Bitgen\Sdk\Tests\Http\FakeTransport;
use Bitgen\Sdk\Tests\TypeErrors;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AssetResourceTest extends TestCase
{
    use TypeErrors;

    /** A realistic `GET /asset/{asset}` body (contract § 3) */
    public const ETH = [
        'uuid' => 'a1',
        'state' => 'AVAILABLE',
        'iso' => 'ETH',
        'label' => 'Ethereum',
        'contractAddress' => '',
        'baseUnit' => 18,
        'gasUnit' => 21000,
        'logo' => null,
        'data' => '{"provider":"x"}',
        'fees' => ['low' => ['maxFee' => 1], 'medium' => null, 'high' => 'fast', 'computed' => ['gas' => '21000', 'native' => '0.000021']],
        'ticker' => ['price' => 2031.5, 'marketcap' => 244000000000, 'rank' => 2, 'percentChange24h' => -1.2],
        'history' => ['d' => [[1700000000, 2000.5], [1700003600, 2031.5]], 'w' => [], 'm' => [[1699000000, 1900]], 'y' => [], 'all' => []],
        'network' => ['uuid' => 'n1', 'state' => 'ENABLED', 'caip2' => 'eip155:1', 'label' => 'Ethereum', 'gasBase' => 1, 'data' => '{}', 'type' => ['uuid' => 't1', 'code' => 'EVM', 'label' => 'EVM', 'data' => '{}']],
        'somethingNew' => 'ignored',
    ];

    private FakeTransport $transport;
    private AssetResource $asset;

    protected function setUp(): void
    {
        $this->transport = new FakeTransport();
        $this->asset = new AssetResource(new HttpClient($this->transport, 'org-uuid', 'k', 'https://api.test', 1000, 'ua'));
    }

    private static function json(mixed $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    public function testListMapsEveryAssetOfThePage(): void
    {
        $this->transport->willAnswer(200, self::json(['count' => 2, 'items' => [self::ETH, ['uuid' => 'a2', 'state' => 'HIDDEN', 'iso' => 'BTC']]]));
        $page = $this->asset->list();

        self::assertSame('GET', $this->transport->last()['method']);
        self::assertSame('https://api.test/asset', $this->transport->last()['url']);
        self::assertSame(2, $page->count);
        self::assertCount(2, $page->items);
        self::assertSame('ETH', $page->items[0]->iso);
        self::assertSame(AssetState::HIDDEN, $page->items[1]->state);
        self::assertSame('BTC', $page->items[1]->iso);
    }

    public function testGetMapsEveryFieldOfTheContract(): void
    {
        $this->transport->willAnswer(200, self::json(self::ETH));
        $eth = $this->asset->get(Asset::ETH);

        self::assertSame('https://api.test/asset/eth', $this->transport->last()['url']);
        self::assertSame('a1', $eth->uuid);
        self::assertSame(AssetState::AVAILABLE, $eth->state);
        self::assertSame('ETH', $eth->iso);
        self::assertSame('Ethereum', $eth->label);
        self::assertSame('', $eth->contractAddress);
        self::assertSame(18, $eth->baseUnit);
        self::assertSame(21000, $eth->gasUnit);
        self::assertNull($eth->logo);
        self::assertSame('{"provider":"x"}', $eth->data);
        self::assertSame(['maxFee' => 1], $eth->fees->low);
        self::assertNull($eth->fees->medium);
        self::assertSame('fast', $eth->fees->high);
        self::assertSame('21000', $eth->fees->computed->gas);
        self::assertSame('0.000021', $eth->fees->computed->native);
        self::assertSame(2031.5, $eth->ticker->price);
        self::assertSame(244000000000.0, $eth->ticker->marketcap);
        self::assertSame(2, $eth->ticker->rank);
        self::assertSame(-1.2, $eth->ticker->percentChange24h);
        self::assertSame([[1700000000, 2000.5], [1700003600, 2031.5]], $eth->history->d);
        self::assertSame([], $eth->history->w);
        self::assertSame([[1699000000, 1900.0]], $eth->history->m);
        self::assertSame('n1', $eth->network->uuid);
        self::assertSame('ENABLED', $eth->network->state);
        self::assertSame('eip155:1', $eth->network->caip2);
        self::assertSame(1, $eth->network->gasBase);
        self::assertSame('EVM', $eth->network->type->code);
        self::assertSame('t1', $eth->network->type->uuid);
    }

    public function testGetSendsTheValueAsIsEncodedAndAcceptsAUuidOrAnIso(): void
    {
        $this->transport->willAnswer(200, self::json(self::ETH))->willAnswer(200, self::json(self::ETH));
        $this->asset->get('a1');
        self::assertSame('https://api.test/asset/a1', $this->transport->last()['url']);
        $this->asset->get('Eth/x');
        self::assertSame('https://api.test/asset/Eth%2Fx', $this->transport->last()['url']);
    }

    public function testMissingFieldsNeverThrowAndAbsentNullablesAreNull(): void
    {
        $this->transport->willAnswer(200, self::json(['uuid' => 'a1', 'state' => 'ARCHIVED', 'iso' => 'btc']));
        $asset = $this->asset->get(Asset::BTC);

        self::assertSame(AssetState::ARCHIVED, $asset->state);
        self::assertNull($asset->logo);
        self::assertSame('', $asset->label);
        self::assertSame(0, $asset->baseUnit);
        self::assertNull($asset->fees->low);
        self::assertSame('', $asset->fees->computed->gas);
        self::assertSame(0.0, $asset->ticker->price);
        self::assertSame([], $asset->history->all);
        self::assertSame('', $asset->network->type->code);
    }

    public function testTickersAndTicker(): void
    {
        $this->transport->willAnswer(200, self::json(['count' => 1, 'items' => [['iso' => 'BTC', 'ticker' => ['price' => 61230.4, 'marketcap' => 1.2e12, 'rank' => 1, 'percentChange24h' => 0.3]]]]));
        $tickers = $this->asset->tickers();
        self::assertSame('https://api.test/ticker', $this->transport->last()['url']);
        self::assertSame(1, $tickers->count);
        self::assertSame('BTC', $tickers->items[0]->iso);
        self::assertSame(61230.4, $tickers->items[0]->ticker->price);
        self::assertSame(1, $tickers->items[0]->ticker->rank);

        $this->transport->willAnswer(200, self::json(['iso' => 'BTC', 'ticker' => ['price' => 61230.4, 'marketcap' => 1.2e12, 'rank' => 1, 'percentChange24h' => 0.3], 'history' => ['d' => [[1700000000, 61000]]]]));
        $btc = $this->asset->ticker(Asset::BTC);
        self::assertSame('https://api.test/ticker/btc', $this->transport->last()['url']);
        self::assertSame('BTC', $btc->iso);
        self::assertSame([[1700000000, 61000.0]], $btc->history->d);
        self::assertSame([], $btc->history->y);
    }

    public function testApiErrorsBecomeBitgenExceptions(): void
    {
        foreach ([[403, 'forbidden_permission'], [404, 'unknown_asset']] as [$status, $code]) {
            $this->transport->willAnswer($status, self::json(['error' => true, 'message' => $code, 'code' => $status]));
            try {
                $this->asset->get('nope');
                self::fail('expected a BitgenException');
            } catch (BitgenException $e) {
                self::assertSame($status, $e->status);
                self::assertSame($code, $e->errorCode);
            }
        }
        $this->transport->willAnswer(403, self::json(['error' => true, 'message' => 'forbidden_permission', 'code' => 403]));
        try {
            $this->asset->list();
            self::fail('expected a BitgenException');
        } catch (BitgenException $e) {
            self::assertSame('forbidden_permission', $e->errorCode);
        }
    }

    public function testInvalidSegmentsAreRefusedBeforeAnyRequest(): void
    {
        foreach (['', '  ', '.', '..'] as $bad) {
            try {
                $this->asset->get($bad);
                self::fail('expected an InvalidArgumentException');
            } catch (InvalidArgumentException) {
            }
            try {
                $this->asset->ticker($bad);
                self::fail('expected an InvalidArgumentException');
            } catch (InvalidArgumentException) {
            }
        }
        self::assertSame([], $this->transport->requests);
    }

    public function testAStateOutsideTheContractIsKeptAsIs(): void
    {
        $this->transport->willAnswer(200, self::json(['uuid' => 'a1', 'state' => 'BRAND_NEW', 'iso' => 'x']));
        self::assertSame('BRAND_NEW', $this->asset->get('x')->state);
    }

    public function testAnAnswerThatIsNotAnObjectIsAContractViolation(): void
    {
        $this->transport->willAnswer(200, '"just a string"');
        $this->expectException(\UnexpectedValueException::class);
        $this->asset->get(Asset::ETH);
    }

    public function testAnAssetModelIsAcceptedByGetAndItsUuidIsSent(): void
    {
        $this->transport->willAnswer(200, self::json(self::ETH));
        $eth = $this->asset->get(Asset::ETH);
        $this->transport->willAnswer(200, self::json(self::ETH));
        $this->asset->get($eth);
        self::assertSame('https://api.test/asset/' . $eth->uuid, $this->transport->last()['url']);
        $this->transport->willAnswer(200, self::json(self::ETH));
        $this->asset->get(new AssetRef('asset-eth', 'ETH', 'Ethereum'));
        self::assertSame('https://api.test/asset/asset-eth', $this->transport->last()['url']);

        $sent = count($this->transport->requests);
        self::assertTypeError(fn () => $this->asset->ticker($eth)); // @phpstan-ignore argument.type
        try {
            $this->asset->get(new AssetRef('', 'ETH', 'Ethereum'));
            self::fail('expected an InvalidArgumentException');
        } catch (InvalidArgumentException) {
        }
        self::assertCount($sent, $this->transport->requests);
    }
}
