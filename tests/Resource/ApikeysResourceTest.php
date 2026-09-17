<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Tests\Resource;

use Bitgen\Sdk\Exception\BitgenException;
use Bitgen\Sdk\Http\HttpClient;
use Bitgen\Sdk\Model\ApikeyState;
use Bitgen\Sdk\Resource\ApikeysResource;
use Bitgen\Sdk\Tests\Http\FakeTransport;
use Bitgen\Sdk\Tests\TypeErrors;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ApikeysResourceTest extends TestCase
{
    use TypeErrors;

    /** A realistic `GET /organization/{organization}/apikeys/{apikey}` body (contract § 11) */
    public const APIKEY = [
        'uuid' => 'key-1', 'state' => 'ENABLED', 'name' => 'backend', 'permissions' => ['customer.read', 'bank.read', 'custody.write'], 'expireAt' => 1735689600, 'createdAt' => 1700000000,
        'organization' => [
            'uuid' => 'org-uuid', 'state' => 'ENABLED', 'name' => 'ACME',
            'hub' => ['uuid' => 'hub-1', 'state' => 'ENABLED', 'name' => 'HUB', 'options' => ['white_label' => true]],
            'owner' => ['uuid' => 'owner-1', 'login' => 'ceo@acme.fr', 'firstname' => 'Anne', 'lastname' => null],
        ],
        'somethingNew' => true,
    ];

    private FakeTransport $transport;
    private ApikeysResource $apikeys;

    protected function setUp(): void
    {
        $this->transport = new FakeTransport();
        $this->apikeys = new ApikeysResource(new HttpClient($this->transport, 'org-uuid', 'k', 'https://api.test', 1000, 'ua'));
    }

    private static function json(mixed $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    public function testListSendsTheExactQueryAndMapsKeys(): void
    {
        $this->transport->willAnswer(200, self::json(['count' => 1, 'items' => [self::APIKEY]]));
        $page = $this->apikeys->list(includeRevoked: true, offset: 0, limit: 50);

        self::assertSame('GET', $this->transport->last()['method']);
        self::assertSame('https://api.test/organization/org-uuid/apikeys?includeRevoked=true&offset=0&limit=50', $this->transport->last()['url']);
        self::assertSame(1, $page->count);
        self::assertSame('key-1', $page->items[0]->uuid);

        $this->apikeys->list();
        self::assertSame('https://api.test/organization/org-uuid/apikeys', $this->transport->last()['url']);
        $this->apikeys->list(includeRevoked: false);
        self::assertSame('https://api.test/organization/org-uuid/apikeys?includeRevoked=false', $this->transport->last()['url']);
    }

    public function testGetMapsTheKey(): void
    {
        $this->transport->willAnswer(200, self::json(self::APIKEY));
        $key = $this->apikeys->get('key-1');

        self::assertSame('https://api.test/organization/org-uuid/apikeys/key-1', $this->transport->last()['url']);
        self::assertSame('key-1', $key->uuid);
        self::assertSame(ApikeyState::ENABLED, $key->state);
        self::assertSame('backend', $key->name);
        self::assertSame(['customer.read', 'bank.read', 'custody.write'], $key->permissions);
        self::assertSame(1735689600, $key->expireAt);
        self::assertSame(1700000000, $key->createdAt);
        self::assertSame('org-uuid', $key->organization->uuid);
        self::assertSame('ENABLED', $key->organization->state);
        self::assertSame('ACME', $key->organization->name);
        self::assertNotNull($key->organization->hub);
        self::assertSame('hub-1', $key->organization->hub->uuid);
        self::assertSame('ENABLED', $key->organization->hub->state);
        self::assertSame('HUB', $key->organization->hub->name);
        self::assertSame(['white_label' => true], $key->organization->hub->options);
        self::assertNotNull($key->organization->owner);
        self::assertSame('owner-1', $key->organization->owner->uuid);
        self::assertSame('ceo@acme.fr', $key->organization->owner->login);
        self::assertSame('Anne', $key->organization->owner->firstname);
        self::assertNull($key->organization->owner->lastname);

        // a revoked key of an organization without hub nor owner; the scope is encoded in the path
        $apikeys = new ApikeysResource(new HttpClient($this->transport, 'org/1', 'k', 'https://api.test', 1000, 'ua'));
        $this->transport->willAnswer(200, self::json(['uuid' => 'key-2', 'state' => 'REVOKED', 'name' => 'old', 'permissions' => [], 'expireAt' => 1700000000, 'createdAt' => 1690000000, 'organization' => ['uuid' => 'org/1', 'state' => 'ENABLED', 'name' => 'ACME', 'hub' => null, 'owner' => null]]));
        $revoked = $apikeys->get('key-2');
        self::assertSame('https://api.test/organization/org%2F1/apikeys/key-2', $this->transport->last()['url']);
        self::assertSame(ApikeyState::REVOKED, $revoked->state);
        self::assertSame([], $revoked->permissions);
        self::assertNull($revoked->organization->hub);
        self::assertNull($revoked->organization->owner);
    }

    public function testLogsMapTheCalls(): void
    {
        $this->transport->willAnswer(200, self::json(['count' => 2, 'items' => [
            ['date' => 1700000000, 'path' => 'GET /custody/c-1', 'payload' => '{"user":"c-1"}', 'status' => 200, 'error' => null],
            ['date' => 1700000060, 'path' => 'PUT /bank/c-1', 'payload' => '{"amount":"50.00","iban":"****"}', 'status' => 416, 'error' => '{"error":true,"message":"requested_amount_error","code":416}'],
        ]]));
        $page = $this->apikeys->logs('key-1', offset: 0, limit: 50);

        self::assertSame('https://api.test/organization/org-uuid/apikeys/key-1/logs?offset=0&limit=50', $this->transport->last()['url']);
        self::assertSame(2, $page->count);
        self::assertSame(1700000000, $page->items[0]->date);
        self::assertSame('GET /custody/c-1', $page->items[0]->path);
        self::assertSame('{"user":"c-1"}', $page->items[0]->payload);
        self::assertSame(200, $page->items[0]->status);
        self::assertNull($page->items[0]->error);
        self::assertSame(416, $page->items[1]->status);
        self::assertSame('{"error":true,"message":"requested_amount_error","code":416}', $page->items[1]->error);

        $this->apikeys->logs('key-1');
        self::assertSame('https://api.test/organization/org-uuid/apikeys/key-1/logs', $this->transport->last()['url']);
    }

    public function testApiErrorsBecomeBitgenExceptions(): void
    {
        $cases = [
            [404, 'unknown_apikey', fn () => $this->apikeys->get('key-x')],
            [422, 'invalid_include_revoked', fn () => $this->apikeys->list(includeRevoked: true)],
            [403, 'forbidden_permission', fn () => $this->apikeys->logs('key-1')],
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

    public function testInvalidSegmentsAreRefusedBeforeAnyRequest(): void
    {
        foreach ([fn () => $this->apikeys->get(''), fn () => $this->apikeys->logs('..')] as $call) {
            try {
                $call();
                self::fail('expected an InvalidArgumentException');
            } catch (InvalidArgumentException) {
            }
        }
        self::assertSame([], $this->transport->requests);
    }

    public function testTheKeyModelIsAccepted(): void
    {
        $this->transport->willAnswer(200, self::json(self::APIKEY));
        $key = $this->apikeys->get('key-1');
        $this->transport->willAnswer(200, self::json(self::APIKEY));
        $this->apikeys->get($key);
        self::assertSame('https://api.test/organization/org-uuid/apikeys/key-1', $this->transport->last()['url']);
        $this->transport->willAnswer(200, self::json(['count' => 0, 'items' => []]));
        $this->apikeys->logs($key, limit: 10);
        self::assertSame('https://api.test/organization/org-uuid/apikeys/key-1/logs?limit=10', $this->transport->last()['url']);

        $sent = count($this->transport->requests);
        self::assertTypeError(fn () => $this->apikeys->get($key->organization)); // @phpstan-ignore argument.type
        self::assertCount($sent, $this->transport->requests);
    }
}
