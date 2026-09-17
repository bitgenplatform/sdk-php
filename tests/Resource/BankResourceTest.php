<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Tests\Resource;

use Bitgen\Sdk\Exception\BitgenException;
use Bitgen\Sdk\Http\HttpClient;
use Bitgen\Sdk\Model\BankDirection;
use Bitgen\Sdk\Model\Created;
use Bitgen\Sdk\Resource\BankResource;
use Bitgen\Sdk\Tests\Http\FakeTransport;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class BankResourceTest extends TestCase
{
    /** A realistic `GET /bank/{user}` body (contract § 5) */
    public const ACCOUNT = [
        'uuid' => 'b-1', 'message' => 'BTGN-4242', 'iban' => 'FR7630006000011234567890189', 'bank' => 'BNP', 'bic' => 'BNPAFRPP',
        'balance' => 150.5, 'history' => ['d' => [[1700000000, 100.0], [1700003600, 150.5]], 'w' => [], 'm' => [], 'y' => [], 'all' => []],
        'pending' => ['in' => 20, 'out' => 0], 'somethingNew' => true,
    ];

    private FakeTransport $transport;
    private BankResource $bank;

    protected function setUp(): void
    {
        $this->transport = new FakeTransport();
        $this->bank = new BankResource(new HttpClient($this->transport, 'org-uuid', 'k', 'https://api.test', 1000, 'ua'));
    }

    private static function json(mixed $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    public function testGetMapsTheAccount(): void
    {
        $this->transport->willAnswer(200, self::json(self::ACCOUNT));
        $account = $this->bank->get(new Created('c-1'));

        self::assertSame('GET', $this->transport->last()['method']);
        self::assertSame('https://api.test/bank/c-1', $this->transport->last()['url']);
        self::assertSame('b-1', $account->uuid);
        self::assertSame('BTGN-4242', $account->message);
        self::assertSame('FR7630006000011234567890189', $account->iban);
        self::assertSame('BNP', $account->bank);
        self::assertSame('BNPAFRPP', $account->bic);
        self::assertSame(150.5, $account->balance);
        self::assertNotNull($account->history);
        self::assertSame([[1700000000, 100.0], [1700003600, 150.5]], $account->history->d);
        self::assertSame(20.0, $account->pending->in);
        self::assertSame(0.0, $account->pending->out);

        // no bank details yet, history not materialized yet
        $this->transport->willAnswer(200, self::json(['uuid' => 'b-2', 'message' => 'BTGN-1', 'iban' => null, 'bank' => null, 'bic' => null, 'balance' => 0, 'history' => [], 'pending' => ['in' => 0, 'out' => 0]]));
        $fresh = $this->bank->get('jean@valjean.fr');
        self::assertSame('https://api.test/bank/jean%40valjean.fr', $this->transport->last()['url']);
        self::assertNull($fresh->iban);
        self::assertNull($fresh->history);
        self::assertSame(0.0, $fresh->balance);
    }

    public function testOperationsSendTheExactQueryAndMapOperations(): void
    {
        $this->transport->willAnswer(200, self::json(['count' => 2, 'items' => [
            ['txId' => 't-1', 'amount' => 150.5, 'direction' => 'DEPOSIT', 'date' => 1700000000, 'info' => null],
            ['txId' => 't-2', 'amount' => 25, 'direction' => 'PURCHASE', 'date' => 1700003600, 'info' => 'ETH'],
        ]]));
        $page = $this->bank->operations('c-1', direction: BankDirection::DEPOSIT, from: 1699000000, to: 1701000000, offset: 0, limit: 50);

        self::assertSame('https://api.test/bank/c-1/operations?direction=DEPOSIT&from=1699000000&to=1701000000&offset=0&limit=50', $this->transport->last()['url']);
        self::assertSame(2, $page->count);
        self::assertSame('t-1', $page->items[0]->txId);
        self::assertSame(150.5, $page->items[0]->amount);
        self::assertSame(BankDirection::DEPOSIT, $page->items[0]->direction);
        self::assertSame(1700000000, $page->items[0]->date);
        self::assertNull($page->items[0]->info);
        self::assertSame(25.0, $page->items[1]->amount);
        self::assertSame('ETH', $page->items[1]->info);

        $this->bank->operations('c-1');
        self::assertSame('https://api.test/bank/c-1/operations', $this->transport->last()['url']);
        $this->bank->operations('c-1', direction: BankDirection::SELL);
        self::assertSame('https://api.test/bank/c-1/operations?direction=SELL', $this->transport->last()['url']);
    }

    public function testWithdrawSendsTheAmountAsAStringAndTheBankDetailsWhenGiven(): void
    {
        $this->transport->willAnswer(200, '{"transaction":"tx-1"}');
        $withdrawal = $this->bank->withdraw('c-1', 50);
        self::assertSame('tx-1', $withdrawal->transaction);
        self::assertSame('PUT', $this->transport->last()['method']);
        self::assertSame('https://api.test/bank/c-1', $this->transport->last()['url']);
        self::assertSame('{"amount":"50"}', $this->transport->last()['body']);

        $this->bank->withdraw('c-1', '0.000000000000000001', iban: 'FR76…', bic: 'BNPAFRPP');
        self::assertSame('{"amount":"0.000000000000000001","iban":"FR76…","bic":"BNPAFRPP"}', $this->transport->last()['body']);

        $this->bank->withdraw('c-1', 12.5, bank: 'BNP');
        self::assertSame('{"amount":"12.5","bank":"BNP"}', $this->transport->last()['body']);
    }

    public function testCreditTargetsAUserOrAWireMessage(): void
    {
        $this->transport->willAnswer(201, '{"uuid":"d-1"}');
        $credit = $this->bank->credit('100.00', user: new Created('c-1'), reference: 'BANK-REF-42');
        self::assertSame('d-1', $credit->uuid);
        self::assertSame('POST', $this->transport->last()['method']);
        self::assertSame('https://api.test/bank', $this->transport->last()['url']);
        self::assertSame('{"amount":"100.00","user":"c-1","reference":"BANK-REF-42"}', $this->transport->last()['body']);

        $this->bank->credit(100, message: 'BTGN-4242', currency: 'EUR');
        self::assertSame('{"amount":"100","currency":"EUR","message":"BTGN-4242"}', $this->transport->last()['body']);
    }

    public function testApiErrorsBecomeBitgenExceptions(): void
    {
        $cases = [
            [412, 'owner_identity_not_validated', fn () => $this->bank->get('c-1')],
            [412, 'ramp_not_enabled', fn () => $this->bank->withdraw('c-1', '10')],
            [412, 'deposit_reported_by_provider', fn () => $this->bank->credit('10', message: 'BTGN-1')],
            [416, 'invalid_amount', fn () => $this->bank->withdraw('c-1', '0')],
            [403, 'forbidden_permission', fn () => $this->bank->operations('c-1')],
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

    public function testInvalidAmountsAndUsersAreRefusedBeforeAnyRequest(): void
    {
        foreach ([-1, 1e-8, '', INF] as $amount) {
            try {
                $this->bank->withdraw('c-1', $amount);
                self::fail('expected an InvalidArgumentException');
            } catch (InvalidArgumentException) {
            }
            try {
                $this->bank->credit($amount, user: 'c-1');
                self::fail('expected an InvalidArgumentException');
            } catch (InvalidArgumentException) {
            }
        }
        try {
            $this->bank->get('');
            self::fail('expected an InvalidArgumentException');
        } catch (InvalidArgumentException) {
        }
        try {
            $this->bank->operations('c-1', direction: 'deposit');   // a typo would silently mean ALL for the API
            self::fail('expected an InvalidArgumentException');
        } catch (InvalidArgumentException) {
        }
        self::assertSame([], $this->transport->requests);
    }
}
