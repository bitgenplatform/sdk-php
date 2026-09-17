<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Tests\Resource;

use Bitgen\Sdk\Asset;
use Bitgen\Sdk\Exception\BitgenException;
use Bitgen\Sdk\Http\HttpClient;
use Bitgen\Sdk\Model\CoreState;
use Bitgen\Sdk\Model\CoreType;
use Bitgen\Sdk\Resource\CoreResource;
use Bitgen\Sdk\Tests\Http\FakeTransport;
use Bitgen\Sdk\Tests\TypeErrors;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CoreResourceTest extends TestCase
{
    use TypeErrors;

    /** A realistic `STAKING` core (contract § 9 bis) */
    public const STAKING_CORE = [
        'uuid' => 'core-figment-sol', 'state' => 'ENABLED', 'name' => 'figment_sol', 'label' => 'Figment SOL', 'type' => 'STAKING',
        'asset' => ['uuid' => 'asset-sol', 'iso' => 'SOL', 'label' => 'Solana'],
        'config' => [
            ['name' => 'connector', 'label' => ['fr' => 'Connecteur', 'en' => 'Connector'], 'data' => ['type' => 'string', 'value' => 'figment']],
            ['name' => 'apr', 'label' => ['fr' => 'Taux annuel', 'en' => 'Annual rate'], 'data' => ['type' => 'string', 'value' => '6.5']],
            ['name' => 'min_deposit', 'label' => ['fr' => 'Dépôt minimum', 'en' => 'Minimum deposit'], 'data' => ['type' => 'string', 'value' => '1']],
            ['name' => 'api_key', 'label' => ['fr' => 'Clé', 'en' => 'Key'], 'data' => ['type' => 'password', 'value' => '']],
            'not an object',
        ],
        'somethingNew' => true,
    ];

    /** A bank connector: no asset */
    public const RAMP_CORE = ['uuid' => 'core-bank', 'state' => 'DISABLED', 'name' => 'manual_bank', 'label' => 'Manual bank', 'type' => 'RAMP', 'asset' => null, 'config' => []];

    private FakeTransport $transport;
    private CoreResource $core;

    protected function setUp(): void
    {
        $this->transport = new FakeTransport();
        $this->core = new CoreResource(new HttpClient($this->transport, 'org-uuid', 'k', 'https://api.test', 1000, 'ua'));
    }

    private static function json(mixed $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    public function testListSendsTheExactQueryAndMapsCores(): void
    {
        $this->transport->willAnswer(200, self::json(['count' => 1, 'items' => [self::STAKING_CORE]]));
        $page = $this->core->list(type: CoreType::STAKING, asset: Asset::ETH);

        self::assertSame('GET', $this->transport->last()['method']);
        self::assertSame('https://api.test/applications/core?type=STAKING&asset=eth', $this->transport->last()['url']);
        self::assertSame(1, $page->count);
        $core = $page->items[0];
        self::assertSame('core-figment-sol', $core->uuid);
        self::assertSame(CoreState::ENABLED, $core->state);
        self::assertSame('figment_sol', $core->name);
        self::assertSame('Figment SOL', $core->label);
        self::assertSame(CoreType::STAKING, $core->type);
        self::assertNotNull($core->asset);
        self::assertSame('asset-sol', $core->asset->uuid);
        self::assertSame('SOL', $core->asset->iso);
        self::assertSame('Solana', $core->asset->label);
        self::assertCount(4, $core->config);   // the item that is not an object is skipped
        self::assertSame('connector', $core->config[0]->name);
        self::assertSame(['fr' => 'Connecteur', 'en' => 'Connector'], $core->config[0]->label);
        self::assertSame('string', $core->config[0]->data->type);
        self::assertSame('figment', $core->config[0]->data->value);
        self::assertSame('6.5', $core->config[1]->data->value);
        self::assertSame('password', $core->config[3]->data->type);
        self::assertSame('', $core->config[3]->data->value);

        $this->transport->willAnswer(200, self::json(['count' => 1, 'items' => [self::RAMP_CORE]]));
        $banks = $this->core->list(type: CoreType::RAMP, state: CoreState::DISABLED);
        self::assertSame('https://api.test/applications/core?type=RAMP&state=DISABLED', $this->transport->last()['url']);
        self::assertNull($banks->items[0]->asset);
        self::assertSame([], $banks->items[0]->config);
        self::assertSame(CoreState::DISABLED, $banks->items[0]->state);

        $this->core->list();
        self::assertSame('https://api.test/applications/core', $this->transport->last()['url']);
        $this->core->list(asset: 'asset-sol', state: CoreState::ENABLED);
        self::assertSame('https://api.test/applications/core?asset=asset-sol&state=ENABLED', $this->transport->last()['url']);
    }

    public function testGetMapsTheCore(): void
    {
        $this->transport->willAnswer(200, self::json(self::STAKING_CORE));
        $core = $this->core->get('core-figment-sol');

        self::assertSame('https://api.test/applications/core/core-figment-sol', $this->transport->last()['url']);
        self::assertSame('figment_sol', $core->name);
        self::assertSame('min_deposit', $core->config[2]->name);
        self::assertSame('1', $core->config[2]->data->value);
    }

    public function testApiErrorsBecomeBitgenExceptions(): void
    {
        $cases = [
            [424, 'unknown_core_type', fn () => $this->core->list(type: CoreType::RAMP)],
            [400, 'invalid_core_status', fn () => $this->core->list(state: CoreState::ENABLED)],
            [404, 'unknown_core', fn () => $this->core->get('core-x')],
            [404, 'unknown_asset', fn () => $this->core->list(type: CoreType::STAKING, asset: 'xyz')],
            [403, 'forbidden_permission', fn () => $this->core->list()],
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
        $cases = [
            ['type must be IDENTITY, AML, TRADING, CUSTODY, STAKING or RAMP', fn () => $this->core->list(type: 'staking')],
            ['state must be ENABLED or DISABLED', fn () => $this->core->list(state: 'enabled')],
        ];
        foreach ($cases as [$message, $call]) {
            try {
                $call();
                self::fail('expected an InvalidArgumentException');
            } catch (InvalidArgumentException $e) {
                self::assertSame($message, $e->getMessage());
            }
        }
        foreach ([fn () => $this->core->get(''), fn () => $this->core->get('.')] as $call) {
            try {
                $call();
                self::fail('expected an InvalidArgumentException');
            } catch (InvalidArgumentException) {
            }
        }
        self::assertSame([], $this->transport->requests);
    }

    public function testModelsAreAcceptedForTheCoreAndTheAsset(): void
    {
        $this->transport->willAnswer(200, self::json(self::STAKING_CORE));
        $core = $this->core->get('core-figment-sol');
        $this->transport->willAnswer(200, self::json(self::STAKING_CORE));
        $this->core->get($core);
        self::assertSame('https://api.test/applications/core/core-figment-sol', $this->transport->last()['url']);
        self::assertNotNull($core->asset);
        $this->core->list(type: CoreType::STAKING, asset: $core->asset);
        self::assertSame('https://api.test/applications/core?type=STAKING&asset=asset-sol', $this->transport->last()['url']);

        $sent = count($this->transport->requests);
        self::assertTypeError(fn () => $this->core->get($core->asset)); // @phpstan-ignore argument.type
        self::assertCount($sent, $this->transport->requests);
    }
}
