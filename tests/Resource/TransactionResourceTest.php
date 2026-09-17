<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Tests\Resource;

use Bitgen\Sdk\Asset;
use Bitgen\Sdk\Exception\BitgenException;
use Bitgen\Sdk\Http\HttpClient;
use Bitgen\Sdk\Model\AssetRef;
use Bitgen\Sdk\Model\Created;
use Bitgen\Sdk\Model\TransactionDirection;
use Bitgen\Sdk\Model\TransactionSource;
use Bitgen\Sdk\Model\TransactionState;
use Bitgen\Sdk\Resource\TransactionResource;
use Bitgen\Sdk\Tests\Http\FakeTransport;
use Bitgen\Sdk\Tests\TypeErrors;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TransactionResourceTest extends TestCase
{
    use TypeErrors;

    public const OWNER = ['uuid' => 'c-1', 'state' => 'ENABLED', 'login' => 'jean@valjean.fr', 'account' => ['firstname' => 'Jean', 'lastname' => 'Valjean', 'fin' => null]];

    /** A realistic `GET /transaction/{transaction}` body (contract § 8), on hold with a compliance alert */
    public const TRANSACTION = [
        'uuid' => 'tx-1', 'state' => 'PENDING', 'source' => 'CUSTODY', 'direction' => 'OUT', 'asset' => 'ETH', 'amount' => 0.5, 'eurValue' => 1015.75,
        'reference' => 'withdraw-42', 'credited' => false, 'silent' => false, 'data' => ['targetAddress' => '0xdef', 'score' => 12],
        'createdAt' => 1700000000, 'updatedAt' => 1700003600,
        'owner' => self::OWNER,
        'assignee' => ['uuid' => 'officer-1', 'state' => 'ENABLED', 'login' => 'compliance@acme.fr', 'account' => ['firstname' => 'Ana', 'lastname' => null, 'fin' => null]],
        'organization' => ['uuid' => 'org-uuid', 'state' => 'ENABLED', 'name' => 'ACME', 'hub' => ['uuid' => 'hub-1', 'name' => 'HUB']],
        'alert' => [
            'uuid' => 'alert-1', 'state' => 'OPEN', 'severity' => 'WARNING', 'type' => 'KYT', 'description' => 'Unusual destination', 'confidence' => 72,
            'recommendation' => 'review', 'factors' => ['new_address'], 'sources' => ['kyt' => ['risk' => 'medium']], 'history' => [['state' => 'OPEN', 'at' => 1700000000]],
            'incidentKey' => 'inc-1', 'createdAt' => 1700000000, 'updatedAt' => 1700000000, 'user' => ['uuid' => 'c-1'], 'assignee' => null, 'organization' => ['uuid' => 'org-uuid'],
        ],
        'somethingNew' => true,
    ];

    private FakeTransport $transport;
    private TransactionResource $transaction;

    protected function setUp(): void
    {
        $this->transport = new FakeTransport();
        $this->transaction = new TransactionResource(new HttpClient($this->transport, 'org-uuid', 'k', 'https://api.test', 1000, 'ua'));
    }

    private static function json(mixed $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    public function testListSendsTheExactQueryAndMapsTransactions(): void
    {
        $this->transport->willAnswer(200, self::json(['count' => 1, 'items' => [self::TRANSACTION]]));
        $page = $this->transaction->list(user: new Created('c-1'), status: TransactionState::PENDING, source: TransactionSource::CUSTODY, direction: TransactionDirection::OUT, asset: Asset::ETH, offset: 0, limit: 100);

        self::assertSame('GET', $this->transport->last()['method']);
        self::assertSame('https://api.test/transaction?user=c-1&status=PENDING&source=CUSTODY&direction=OUT&asset=eth&offset=0&limit=100', $this->transport->last()['url']);
        self::assertSame(1, $page->count);
        self::assertSame('tx-1', $page->items[0]->uuid);

        $this->transaction->list();
        self::assertSame('https://api.test/transaction', $this->transport->last()['url']);
        $this->transaction->list(user: 'c-1', status: TransactionState::COMPLETED, source: TransactionSource::BANK, direction: TransactionDirection::IN, asset: 'EUR');
        self::assertSame('https://api.test/transaction?user=c-1&status=COMPLETED&source=BANK&direction=IN&asset=EUR', $this->transport->last()['url']);
        self::assertNotNull($page->items[0]->owner);
        $this->transaction->list(user: $page->items[0]->owner);   // the owner of a transaction carries a uuid
        self::assertSame('https://api.test/transaction?user=c-1', $this->transport->last()['url']);
    }

    public function testGetMapsTheTransaction(): void
    {
        $this->transport->willAnswer(200, self::json(self::TRANSACTION));
        $tx = $this->transaction->get('tx-1');

        self::assertSame('https://api.test/transaction/tx-1', $this->transport->last()['url']);
        self::assertSame('tx-1', $tx->uuid);
        self::assertSame(TransactionState::PENDING, $tx->state);
        self::assertSame(TransactionSource::CUSTODY, $tx->source);
        self::assertSame(TransactionDirection::OUT, $tx->direction);
        self::assertSame('ETH', $tx->asset);
        self::assertSame(0.5, $tx->amount);
        self::assertSame(1015.75, $tx->eurValue);
        self::assertSame('withdraw-42', $tx->reference);
        self::assertFalse($tx->credited);
        self::assertFalse($tx->silent);
        self::assertSame(['targetAddress' => '0xdef', 'score' => 12], $tx->data);
        self::assertSame(1700000000, $tx->createdAt);
        self::assertSame(1700003600, $tx->updatedAt);
        self::assertNotNull($tx->owner);
        self::assertSame('c-1', $tx->owner->uuid);
        self::assertSame('ENABLED', $tx->owner->state);
        self::assertSame('jean@valjean.fr', $tx->owner->login);
        self::assertSame('Jean', $tx->owner->account->firstname);
        self::assertSame('Valjean', $tx->owner->account->lastname);
        self::assertNull($tx->owner->account->fin);
        self::assertNotNull($tx->assignee);
        self::assertSame('officer-1', $tx->assignee->uuid);
        self::assertNull($tx->assignee->account->lastname);
        self::assertNotNull($tx->organization);
        self::assertSame('org-uuid', $tx->organization->uuid);
        self::assertSame('ENABLED', $tx->organization->state);
        self::assertSame('ACME', $tx->organization->name);
        self::assertNotNull($tx->organization->hub);
        self::assertSame('hub-1', $tx->organization->hub->uuid);
        self::assertSame('HUB', $tx->organization->hub->name);
        self::assertNotNull($tx->alert);
        self::assertSame('alert-1', $tx->alert->uuid);
        self::assertSame('OPEN', $tx->alert->state);
        self::assertSame('WARNING', $tx->alert->severity);
        self::assertSame('KYT', $tx->alert->type);
        self::assertSame('Unusual destination', $tx->alert->description);
        self::assertSame(72.0, $tx->alert->confidence);
        self::assertSame('review', $tx->alert->recommendation);
        self::assertSame(['new_address'], $tx->alert->factors);
        self::assertSame(['kyt' => ['risk' => 'medium']], $tx->alert->sources);
        self::assertSame([['state' => 'OPEN', 'at' => 1700000000]], $tx->alert->history);
        self::assertSame('inc-1', $tx->alert->incidentKey);
        self::assertSame(1700000000, $tx->alert->createdAt);
        self::assertSame(['uuid' => 'c-1'], $tx->alert->user);
        self::assertNull($tx->alert->assignee);
        self::assertSame(['uuid' => 'org-uuid'], $tx->alert->organization);

        // a completed bank deposit: no alert, no assignee, an organization without hub; by reference
        $this->transport->willAnswer(200, self::json([
            'uuid' => 'tx-2', 'state' => 'COMPLETED', 'source' => 'BANK', 'direction' => 'IN', 'asset' => 'EUR', 'amount' => 150, 'eurValue' => null, 'reference' => null,
            'credited' => true, 'silent' => false, 'data' => [], 'createdAt' => 1700000000, 'updatedAt' => 1700000000,
            'owner' => self::OWNER, 'assignee' => null, 'organization' => ['uuid' => 'org-uuid', 'state' => 'ENABLED', 'name' => 'ACME', 'hub' => null], 'alert' => null,
        ]));
        $deposit = $this->transaction->get('BANK-REF-42');
        self::assertSame('https://api.test/transaction/BANK-REF-42', $this->transport->last()['url']);
        self::assertSame(150.0, $deposit->amount);
        self::assertNull($deposit->eurValue);
        self::assertNull($deposit->reference);
        self::assertTrue($deposit->credited);
        self::assertSame([], $deposit->data);
        self::assertNull($deposit->assignee);
        self::assertNotNull($deposit->organization);
        self::assertNull($deposit->organization->hub);
        self::assertNull($deposit->alert);

        // a silent internal leg without owner nor organization
        $this->transport->willAnswer(200, self::json(['uuid' => 'tx-3', 'state' => 'TRANSFERING', 'source' => 'CUSTODY', 'direction' => 'OUT', 'asset' => 'ETH', 'amount' => 0.01, 'silent' => true, 'owner' => null, 'organization' => null]));
        $leg = $this->transaction->get('tx-3');
        self::assertTrue($leg->silent);
        self::assertFalse($leg->credited);
        self::assertNull($leg->owner);
        self::assertNull($leg->organization);
    }

    public function testApiErrorsBecomeBitgenExceptions(): void
    {
        $cases = [
            [400, 'invalid_transaction_state', fn () => $this->transaction->list(status: TransactionState::PENDING)],
            [404, 'unknown_user', fn () => $this->transaction->list(user: 'c-x')],
            [404, 'unknown_transaction', fn () => $this->transaction->get('tx-x')],
            [403, 'forbidden_permission', fn () => $this->transaction->list()],
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
        $states = 'status must be ANALYZING, PENDING, COMPLETED, FROZEN, FAILED, TRANSFERING or SEIZED';
        $cases = [
            [$states, fn () => $this->transaction->list(status: 'pending')],
            [$states, fn () => $this->transaction->list(status: 'TRANSFERRING')],   // the API spells it TRANSFERING
            ['source must be BANK or CUSTODY', fn () => $this->transaction->list(source: 'bank')],
            ['direction must be IN or OUT', fn () => $this->transaction->list(direction: 'DEPOSIT')],
        ];
        foreach ($cases as [$message, $call]) {
            try {
                $call();
                self::fail('expected an InvalidArgumentException');
            } catch (InvalidArgumentException $e) {
                self::assertSame($message, $e->getMessage());
            }
        }
        foreach ([fn () => $this->transaction->list(user: ''), fn () => $this->transaction->get(''), fn () => $this->transaction->get('..')] as $call) {
            try {
                $call();
                self::fail('expected an InvalidArgumentException');
            } catch (InvalidArgumentException) {
            }
        }
        self::assertSame([], $this->transport->requests);
    }

    public function testModelsAreAcceptedForTheTransactionTheCustomerAndTheAsset(): void
    {
        $this->transport->willAnswer(200, self::json(self::TRANSACTION));
        $tx = $this->transaction->get('tx-1');
        $this->transport->willAnswer(200, self::json(self::TRANSACTION));
        $this->transaction->get($tx);
        self::assertSame('https://api.test/transaction/tx-1', $this->transport->last()['url']);
        self::assertNotNull($tx->owner);
        $this->transaction->list(user: $tx->owner, asset: new AssetRef('asset-eth', 'ETH', 'Ethereum'));
        self::assertSame('https://api.test/transaction?user=c-1&asset=asset-eth', $this->transport->last()['url']);

        $sent = count($this->transport->requests);
        self::assertTypeError(fn () => $this->transaction->get($tx->owner)); // @phpstan-ignore argument.type
        self::assertCount($sent, $this->transport->requests);
    }
}
