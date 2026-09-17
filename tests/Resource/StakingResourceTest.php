<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Tests\Resource;

use Bitgen\Sdk\Asset;
use Bitgen\Sdk\Exception\BitgenException;
use Bitgen\Sdk\Http\HttpClient;
use Bitgen\Sdk\Model\Created;
use Bitgen\Sdk\Model\StakingMovementKind;
use Bitgen\Sdk\Model\StakingMovementState;
use Bitgen\Sdk\Model\StakingPositionState;
use Bitgen\Sdk\Resource\CoreResource;
use Bitgen\Sdk\Resource\StakingResource;
use Bitgen\Sdk\Tests\Http\FakeTransport;
use Bitgen\Sdk\Tests\TypeErrors;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class StakingResourceTest extends TestCase
{
    use TypeErrors;

    /** A realistic `GET /staking/{movement}` body (contract § 9) */
    public const MOVEMENT = [
        'uuid' => 'mv-1', 'state' => 'COMPLETED', 'kind' => 'STAKE', 'provider' => 'figment_sol', 'amount' => '2', 'createdAt' => 1700000000, 'updatedAt' => 1700003600,
        'staking' => [
            'uuid' => 'pos-1', 'state' => 'ENABLED', 'amount' => '2', 'error' => null, 'data' => ['rewards' => '0.0123', 'lastRewardAt' => 1700090000],
            'createdAt' => 1700000000, 'updatedAt' => 1700090000, 'core' => ['uuid' => 'core-figment-sol', 'name' => 'figment_sol', 'label' => 'Figment SOL'],
        ],
        'owner' => TransactionResourceTest::OWNER,
        'asset' => ['uuid' => 'asset-sol', 'iso' => 'SOL', 'label' => 'Solana'],
        'organization' => ['uuid' => 'org-uuid', 'state' => 'ENABLED', 'name' => 'ACME'],
        'somethingNew' => true,
    ];

    private FakeTransport $transport;
    private StakingResource $staking;

    protected function setUp(): void
    {
        $this->transport = new FakeTransport();
        $http = new HttpClient($this->transport, 'org-uuid', 'k', 'https://api.test', 1000, 'ua');
        $this->staking = new StakingResource($http, new CoreResource($http));
    }

    private static function json(mixed $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    public function testProvidersDelegateToTheCoreCatalogue(): void
    {
        $this->transport->willAnswer(200, self::json(['count' => 1, 'items' => [CoreResourceTest::STAKING_CORE]]));
        $providers = $this->staking->providers(Asset::SOL);

        self::assertSame('https://api.test/applications/core?type=STAKING&asset=sol', $this->transport->last()['url']);
        self::assertSame(1, $providers->count);
        self::assertSame('figment_sol', $providers->items[0]->name);
        self::assertNotNull($providers->items[0]->asset);
        self::assertSame('SOL', $providers->items[0]->asset->iso);

        $this->staking->providers();
        self::assertSame('https://api.test/applications/core?type=STAKING', $this->transport->last()['url']);
        $this->staking->providers('asset-eth');
        self::assertSame('https://api.test/applications/core?type=STAKING&asset=asset-eth', $this->transport->last()['url']);
    }

    public function testStakeSendsTheExactBody(): void
    {
        $this->transport->willAnswer(201, '{"uuid":"mv-1"}');
        $created = $this->staking->stake(new Created('c-1'), Asset::SOL, '2', 'figment_sol');
        self::assertSame('mv-1', $created->uuid);
        self::assertSame('POST', $this->transport->last()['method']);
        self::assertSame('https://api.test/staking', $this->transport->last()['url']);
        self::assertSame('{"user":"c-1","asset":"sol","amount":"2","provider":"figment_sol"}', $this->transport->last()['body']);

        $this->staking->stake('c-1', Asset::ETH, 0.5, 'core-bitgen-eth');   // the provider by uuid
        self::assertSame('{"user":"c-1","asset":"eth","amount":"0.5","provider":"core-bitgen-eth"}', $this->transport->last()['body']);
    }

    public function testListAndMovementsHaveTheirOwnPaths(): void
    {
        $this->transport->willAnswer(200, self::json(['count' => 1, 'items' => [self::MOVEMENT]]));
        $page = $this->staking->list(user: new Created('c-1'), direction: StakingMovementKind::STAKE, offset: 0, limit: 50);
        self::assertSame('https://api.test/staking?user=c-1&direction=STAKE&offset=0&limit=50', $this->transport->last()['url']);
        self::assertSame(1, $page->count);
        self::assertSame('mv-1', $page->items[0]->uuid);

        $this->staking->list();
        self::assertSame('https://api.test/staking', $this->transport->last()['url']);

        $this->transport->willAnswer(200, self::json(['count' => 0, 'items' => []]));
        $pending = $this->staking->movements(user: 'c-1', direction: StakingMovementKind::REWARD);
        self::assertSame('https://api.test/staking/movements?user=c-1&direction=REWARD', $this->transport->last()['url']);
        self::assertSame(0, $pending->count);
        self::assertSame([], $pending->items);

        $this->staking->movements();
        self::assertSame('https://api.test/staking/movements', $this->transport->last()['url']);
    }

    public function testGetMapsTheMovementAndItsPosition(): void
    {
        $this->transport->willAnswer(200, self::json(self::MOVEMENT));
        $movement = $this->staking->get('mv-1');

        self::assertSame('https://api.test/staking/mv-1', $this->transport->last()['url']);   // not /staking/movements
        self::assertSame('mv-1', $movement->uuid);
        self::assertSame(StakingMovementState::COMPLETED, $movement->state);
        self::assertSame(StakingMovementKind::STAKE, $movement->kind);
        self::assertSame('figment_sol', $movement->provider);
        self::assertSame('2', $movement->amount);
        self::assertSame(1700000000, $movement->createdAt);
        self::assertSame(1700003600, $movement->updatedAt);
        self::assertSame('pos-1', $movement->staking->uuid);
        self::assertSame(StakingPositionState::ENABLED, $movement->staking->state);
        self::assertSame('2', $movement->staking->amount);
        self::assertNull($movement->staking->error);
        self::assertSame('0.0123', $movement->staking->data->rewards);
        self::assertSame(1700090000, $movement->staking->data->lastRewardAt);
        self::assertSame(1700000000, $movement->staking->createdAt);
        self::assertSame(1700090000, $movement->staking->updatedAt);
        self::assertSame('core-figment-sol', $movement->staking->core->uuid);
        self::assertSame('figment_sol', $movement->staking->core->name);
        self::assertSame('Figment SOL', $movement->staking->core->label);
        self::assertSame('c-1', $movement->owner->uuid);
        self::assertSame('jean@valjean.fr', $movement->owner->login);
        self::assertSame('Jean', $movement->owner->account->firstname);
        self::assertSame('SOL', $movement->asset->iso);
        self::assertNotNull($movement->organization);
        self::assertSame('ACME', $movement->organization->name);
        self::assertNull($movement->organization->hub);

        // a failed request: position FAILED with an error, no rewards yet, no organization
        $this->transport->willAnswer(200, self::json(['uuid' => 'mv-2', 'state' => 'FAILED', 'kind' => 'STAKE', 'provider' => 'bitgen_eth', 'amount' => '0.5', 'createdAt' => 1700000000, 'updatedAt' => 1700000000,
            'staking' => ['uuid' => 'pos-2', 'state' => 'FAILED', 'amount' => '0', 'error' => 'custody_vault_unavailable', 'data' => [], 'createdAt' => 1700000000, 'updatedAt' => 1700000000, 'core' => ['uuid' => 'core-bitgen-eth', 'name' => 'bitgen_eth', 'label' => 'BITGEN ETH']],
            'owner' => TransactionResourceTest::OWNER, 'asset' => ['uuid' => 'asset-eth', 'iso' => 'ETH', 'label' => 'Ethereum'], 'organization' => null]));
        $failed = $this->staking->get('mv-2');
        self::assertSame('custody_vault_unavailable', $failed->staking->error);
        self::assertNull($failed->staking->data->rewards);
        self::assertNull($failed->staking->data->lastRewardAt);
        self::assertNull($failed->organization);
    }

    public function testRewardsAndUnstakeSendAnAmountOrAnEmptyObject(): void
    {
        $this->transport->willAnswer(200, '[]');
        $this->staking->rewards('pos-1');
        self::assertSame('PUT', $this->transport->last()['method']);
        self::assertSame('https://api.test/staking/pos-1/rewards', $this->transport->last()['url']);
        self::assertSame('{}', $this->transport->last()['body']);   // an object, not [] — the API reads an absent amount as "everything"

        $this->transport->willAnswer(200, '[]');
        $this->staking->rewards('pos-1', '0.01');
        self::assertSame('{"amount":"0.01"}', $this->transport->last()['body']);

        $this->transport->willAnswer(200, '[]');
        $this->staking->unstake('pos-1', 1);
        self::assertSame('https://api.test/staking/pos-1/unstake', $this->transport->last()['url']);
        self::assertSame('{"amount":"1"}', $this->transport->last()['body']);

        $this->transport->willAnswer(200, '[]');
        $this->staking->unstake('pos-1');
        self::assertSame('{}', $this->transport->last()['body']);
        self::assertCount(4, $this->transport->requests);
    }

    public function testOperationsAndPortfolioAreReadPerCustomer(): void
    {
        $this->transport->willAnswer(200, self::json(['count' => 2, 'items' => [
            ['txId' => 'op-1', 'movement' => 'mv-1', 'asset' => 'SOL', 'kind' => 'STAKE', 'amount' => '2', 'price' => 128.4, 'value' => 256.8, 'event' => 'validated', 'provider' => 'figment_sol', 'date' => 1700003600],
            ['txId' => 'op-2', 'movement' => null, 'asset' => 'SOL', 'kind' => 'REWARD', 'amount' => '0.0123', 'price' => 130, 'value' => 1.6, 'event' => 'reward', 'provider' => 'figment_sol', 'date' => 1700090000],
        ]]));
        $page = $this->staking->operations(new Created('c-1'), offset: 0, limit: 50);

        self::assertSame('https://api.test/staking/c-1/operations?offset=0&limit=50', $this->transport->last()['url']);
        self::assertSame(2, $page->count);
        self::assertSame('op-1', $page->items[0]->txId);
        self::assertSame('mv-1', $page->items[0]->movement);
        self::assertSame('SOL', $page->items[0]->asset);
        self::assertSame(StakingMovementKind::STAKE, $page->items[0]->kind);
        self::assertSame('2', $page->items[0]->amount);
        self::assertSame(128.4, $page->items[0]->price);
        self::assertSame(256.8, $page->items[0]->value);
        self::assertSame('validated', $page->items[0]->event);
        self::assertSame('figment_sol', $page->items[0]->provider);
        self::assertSame(1700003600, $page->items[0]->date);
        self::assertNull($page->items[1]->movement);   // a daily reward
        self::assertSame(130.0, $page->items[1]->price);

        $this->staking->operations('jean@valjean.fr');
        self::assertSame('https://api.test/staking/jean%40valjean.fr/operations', $this->transport->last()['url']);

        $this->transport->willAnswer(200, self::json([
            'uuid' => 'stk-1', 'balances' => ['capital' => 4060.2, 'revenues' => 25.1],
            'histories' => ['capital' => ['d' => [[1700000000, 4000.0], [1700003600, 4060.2]], 'w' => [], 'm' => [], 'y' => [], 'all' => []], 'revenues' => ['d' => [[1700003600, 25.1]], 'w' => [], 'm' => [], 'y' => [], 'all' => []]],
        ]));
        $portfolio = $this->staking->portfolio('c-1');
        self::assertSame('https://api.test/staking/c-1/portfolio', $this->transport->last()['url']);
        self::assertSame('stk-1', $portfolio->uuid);
        self::assertSame(4060.2, $portfolio->balances->capital);
        self::assertSame(25.1, $portfolio->balances->revenues);
        self::assertSame([[1700000000, 4000.0], [1700003600, 4060.2]], $portfolio->histories->capital->d);
        self::assertSame([[1700003600, 25.1]], $portfolio->histories->revenues->d);
        self::assertSame([], $portfolio->histories->revenues->all);
    }

    public function testApiErrorsBecomeBitgenExceptions(): void
    {
        $cases = [
            [412, 'staking_not_enabled', fn () => $this->staking->stake('c-1', Asset::SOL, '2', 'figment_sol')],
            [412, 'staking_connector_missing', fn () => $this->staking->stake('c-1', Asset::SOL, '2', 'figment_sol')],
            [422, 'amount_below_minimum', fn () => $this->staking->stake('c-1', Asset::SOL, '0.1', 'figment_sol')],
            [416, 'requested_amount_error', fn () => $this->staking->stake('c-1', Asset::SOL, '100', 'figment_sol')],
            [416, 'insufficient_balance', fn () => $this->staking->unstake('pos-1', '100')],
            [416, 'insufficient_rewards', fn () => $this->staking->rewards('pos-1', '100')],
            [425, 'no_rewards', fn () => $this->staking->rewards('pos-1')],
            [404, 'unknown_staking_movement', fn () => $this->staking->get('mv-x')],
            [404, 'unknown_staking', fn () => $this->staking->portfolio('c-x')],
            [404, 'unknown_core', fn () => $this->staking->stake('c-1', Asset::SOL, '2', 'nope')],
            [403, 'forbidden_permission', fn () => $this->staking->list()],
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
        foreach ([fn () => $this->staking->list(direction: 'stake'), fn () => $this->staking->movements(direction: 'DEPOSIT')] as $call) {
            try {
                $call();
                self::fail('expected an InvalidArgumentException');
            } catch (InvalidArgumentException $e) {
                self::assertSame('direction must be STAKE, UNSTAKE, WITHDRAW or REWARD', $e->getMessage());
            }
        }
        $calls = [
            fn () => $this->staking->stake('c-1', Asset::SOL, -1, 'figment_sol'),
            fn () => $this->staking->stake('c-1', Asset::SOL, '', 'figment_sol'),
            fn () => $this->staking->stake('', Asset::SOL, '2', 'figment_sol'),
            fn () => $this->staking->stake(new Created(''), Asset::SOL, '2', 'figment_sol'),
            fn () => $this->staking->rewards('', '1'),
            fn () => $this->staking->rewards('pos-1', 1e-8),
            fn () => $this->staking->unstake('..'),
            fn () => $this->staking->unstake('pos-1', INF),
            fn () => $this->staking->get(''),
            fn () => $this->staking->operations(''),
            fn () => $this->staking->portfolio(new Created(' ')),
            fn () => $this->staking->list(user: ''),
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

    public function testModelsAreAcceptedForTheMovementThePositionTheCustomerAndTheAsset(): void
    {
        $this->transport->willAnswer(200, self::json(self::MOVEMENT));
        $movement = $this->staking->get('mv-1');
        $this->transport->willAnswer(200, self::json(self::MOVEMENT));
        $this->staking->get($movement);
        self::assertSame('https://api.test/staking/mv-1', $this->transport->last()['url']);

        $this->transport->willAnswer(200, '[]');
        $this->staking->rewards($movement->staking);   // the position carried by the movement
        self::assertSame('https://api.test/staking/pos-1/rewards', $this->transport->last()['url']);
        $this->transport->willAnswer(200, '[]');
        $this->staking->unstake($movement->staking, '1');
        self::assertSame('https://api.test/staking/pos-1/unstake', $this->transport->last()['url']);

        $this->transport->willAnswer(201, '{"uuid":"mv-3"}');
        $this->staking->stake($movement->owner, $movement->asset, '2', $movement->staking->core->name);
        self::assertSame('{"user":"c-1","asset":"asset-sol","amount":"2","provider":"figment_sol"}', $this->transport->last()['body']);
        $this->staking->providers($movement->asset);
        self::assertSame('https://api.test/applications/core?type=STAKING&asset=asset-sol', $this->transport->last()['url']);
        $this->staking->operations($movement->owner);
        self::assertSame('https://api.test/staking/c-1/operations', $this->transport->last()['url']);

        $sent = count($this->transport->requests);
        self::assertTypeError(fn () => $this->staking->get($movement->staking)); // @phpstan-ignore argument.type
        self::assertTypeError(fn () => $this->staking->rewards($movement)); // @phpstan-ignore argument.type
        self::assertTypeError(fn () => $this->staking->portfolio(new \stdClass())); // @phpstan-ignore argument.type
        self::assertCount($sent, $this->transport->requests);
    }
}
