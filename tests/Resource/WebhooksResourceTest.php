<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Tests\Resource;

use Bitgen\Sdk\Exception\BitgenException;
use Bitgen\Sdk\Http\HttpClient;
use Bitgen\Sdk\Model\WebhookEventName;
use Bitgen\Sdk\Resource\WebhooksResource;
use Bitgen\Sdk\Tests\Http\FakeTransport;
use Bitgen\Sdk\Tests\TypeErrors;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class WebhooksResourceTest extends TestCase
{
    use TypeErrors;

    /** An event of the catalogue (contract § 10) */
    public const TYPE = ['uuid' => 'wh-1', 'state' => 'ENABLED', 'name' => 'custody.sent', 'label' => '{"fr":"Envoi","en":"Sent"}', 'data' => '{}', 'somethingNew' => true];

    /** A realistic `GET /webhooks/{organization}` body */
    public const SUBSCRIPTIONS = [
        'secret' => 'whsec_0123456789abcdef', 'endpoint' => 'https://example.com/bitgen',
        'items' => [
            ['uuid' => 'sub-1', 'state' => 'ENABLED', 'updatedAt' => 1700000000, 'webhook' => self::TYPE],
            ['uuid' => 'sub-2', 'state' => 'ARCHIVED', 'updatedAt' => 1700003600, 'webhook' => ['uuid' => 'wh-2', 'state' => 'ARCHIVED', 'name' => 'trading.buy', 'label' => '{"fr":"Achat","en":"Buy"}', 'data' => '{"legacy":true}']],
        ],
    ];

    private FakeTransport $transport;
    private WebhooksResource $webhooks;

    protected function setUp(): void
    {
        $this->transport = new FakeTransport();
        $this->webhooks = new WebhooksResource(new HttpClient($this->transport, 'org-uuid', 'k', 'https://api.test', 1000, 'ua'));
    }

    private static function json(mixed $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    public function testSecurityRoutesCarryTheScopeAndTheEndpoint(): void
    {
        $this->transport->willAnswer(201, '[]');
        $this->webhooks->activate('https://example.com/bitgen');
        self::assertSame('POST', $this->transport->last()['method']);
        self::assertSame('https://api.test/webhook/security/org-uuid/activate', $this->transport->last()['url']);
        self::assertSame('{"endpoint":"https://example.com/bitgen"}', $this->transport->last()['body']);

        $this->transport->willAnswer(200, '[]');
        $this->webhooks->updateEndpoint('example.com/bitgen/v2');
        self::assertSame('PATCH', $this->transport->last()['method']);
        self::assertSame('https://api.test/webhook/security/org-uuid', $this->transport->last()['url']);
        self::assertSame('{"endpoint":"example.com/bitgen/v2"}', $this->transport->last()['body']);

        $this->transport->willAnswer(200, '[]');
        $this->webhooks->regenerate();
        self::assertSame('PATCH', $this->transport->last()['method']);
        self::assertSame('https://api.test/webhook/security/org-uuid/regenerate', $this->transport->last()['url']);
        self::assertNull($this->transport->last()['body']);   // no body: the secret is read with list()
    }

    public function testTheScopeIsEncodedAsAPathSegment(): void
    {
        $webhooks = new WebhooksResource(new HttpClient($this->transport, 'org/1 2', 'k', 'https://api.test', 1000, 'ua'));
        $webhooks->regenerate();
        self::assertSame('https://api.test/webhook/security/org%2F1%202/regenerate', $this->transport->last()['url']);
        $this->transport->willAnswer(200, self::json(self::SUBSCRIPTIONS));
        $webhooks->list();
        self::assertSame('https://api.test/webhooks/org%2F1%202', $this->transport->last()['url']);
        $webhooks->subscribe(WebhookEventName::CUSTODY_SENT);
        self::assertSame('{"organization":"org/1 2","event":"custody.sent"}', $this->transport->last()['body']);   // the body carries the scope as is
    }

    public function testListMapsTheSecretTheEndpointAndTheSubscriptions(): void
    {
        $this->transport->willAnswer(200, self::json(self::SUBSCRIPTIONS));
        $subscriptions = $this->webhooks->list(includeArchived: true);

        self::assertSame('GET', $this->transport->last()['method']);
        self::assertSame('https://api.test/webhooks/org-uuid?includeArchived=true', $this->transport->last()['url']);
        self::assertSame('whsec_0123456789abcdef', $subscriptions->secret);
        self::assertSame('https://example.com/bitgen', $subscriptions->endpoint);
        self::assertCount(2, $subscriptions->items);
        self::assertSame('sub-1', $subscriptions->items[0]->uuid);
        self::assertSame('ENABLED', $subscriptions->items[0]->state);
        self::assertSame(1700000000, $subscriptions->items[0]->updatedAt);
        self::assertSame('wh-1', $subscriptions->items[0]->webhook->uuid);
        self::assertSame('ENABLED', $subscriptions->items[0]->webhook->state);
        self::assertSame(WebhookEventName::CUSTODY_SENT, $subscriptions->items[0]->webhook->name);
        self::assertSame('{"fr":"Envoi","en":"Sent"}', $subscriptions->items[0]->webhook->label);
        self::assertSame('{}', $subscriptions->items[0]->webhook->data);
        self::assertSame('ARCHIVED', $subscriptions->items[1]->state);
        self::assertSame(WebhookEventName::TRADING_BUY, $subscriptions->items[1]->webhook->name);

        $this->transport->willAnswer(200, self::json(['secret' => 's', 'endpoint' => 'https://example.com', 'items' => []]));
        $fresh = $this->webhooks->list();
        self::assertSame('https://api.test/webhooks/org-uuid', $this->transport->last()['url']);
        self::assertSame([], $fresh->items);
        $this->webhooks->list(includeArchived: false);
        self::assertSame('https://api.test/webhooks/org-uuid?includeArchived=false', $this->transport->last()['url']);
    }

    public function testSubscribeArchiveAndReactivate(): void
    {
        $this->transport->willAnswer(201, '{"uuid":"sub-1"}');
        $created = $this->webhooks->subscribe(WebhookEventName::CUSTODY_SENT);
        self::assertSame('sub-1', $created->uuid);
        self::assertSame('POST', $this->transport->last()['method']);
        self::assertSame('https://api.test/webhooks', $this->transport->last()['url']);
        self::assertSame('{"organization":"org-uuid","event":"custody.sent"}', $this->transport->last()['body']);

        $this->transport->willAnswer(201, '{"uuid":"sub-3"}');
        $this->webhooks->subscribe('wh-3');   // by uuid of the catalogue, or any name: the catalogue may grow
        self::assertSame('{"organization":"org-uuid","event":"wh-3"}', $this->transport->last()['body']);

        $this->transport->willAnswer(200, '[]');
        $this->webhooks->archive('sub-1');
        self::assertSame('DELETE', $this->transport->last()['method']);
        self::assertSame('https://api.test/webhooks/sub-1', $this->transport->last()['url']);
        self::assertNull($this->transport->last()['body']);

        $this->transport->willAnswer(201, '[]');
        $this->webhooks->reactivate('sub-1');
        self::assertSame('POST', $this->transport->last()['method']);
        self::assertSame('https://api.test/webhooks/sub-1', $this->transport->last()['url']);
        self::assertNull($this->transport->last()['body']);   // no body: the transport sends Content-Length: 0
    }

    public function testLogsMapTheDeliveryAttempts(): void
    {
        $this->transport->willAnswer(200, self::json(['count' => 2, 'items' => [
            ['date' => 1700000000, 'webhook' => 'custody.sent', 'url' => 'https://example.com/bitgen', 'status' => 'SENT', 'http_code' => 204, 'duration_ms' => 87, 'attempts' => 1, 'payload' => ['delivery_id' => 'd-1', 'event' => 'custody.sent'], 'error' => null],
            ['date' => 1700000060, 'webhook' => 'custody.sent', 'url' => 'https://example.com/bitgen', 'status' => 'FAILED', 'http_code' => null, 'duration_ms' => null, 'attempts' => 2, 'payload' => [], 'error' => 'connection refused'],
        ]]));
        $page = $this->webhooks->logs('sub-1', offset: 0, limit: 50);

        self::assertSame('https://api.test/webhooks/sub-1/logs?offset=0&limit=50', $this->transport->last()['url']);
        self::assertSame(2, $page->count);
        self::assertSame(1700000000, $page->items[0]->date);
        self::assertSame(WebhookEventName::CUSTODY_SENT, $page->items[0]->webhook);
        self::assertSame('https://example.com/bitgen', $page->items[0]->url);
        self::assertSame('SENT', $page->items[0]->status);
        self::assertSame(204, $page->items[0]->http_code);
        self::assertSame(87, $page->items[0]->duration_ms);
        self::assertSame(1, $page->items[0]->attempts);
        self::assertSame(['delivery_id' => 'd-1', 'event' => 'custody.sent'], $page->items[0]->payload);
        self::assertNull($page->items[0]->error);
        self::assertNull($page->items[1]->http_code);
        self::assertNull($page->items[1]->duration_ms);
        self::assertSame([], $page->items[1]->payload);
        self::assertSame('connection refused', $page->items[1]->error);

        $this->webhooks->logs('sub-1');
        self::assertSame('https://api.test/webhooks/sub-1/logs', $this->transport->last()['url']);
    }

    public function testCatalogAndCatalogItem(): void
    {
        $this->transport->willAnswer(200, self::json(['count' => 1, 'items' => [self::TYPE]]));
        $catalog = $this->webhooks->catalog();
        self::assertSame('https://api.test/webhook', $this->transport->last()['url']);
        self::assertSame(1, $catalog->count);
        self::assertSame(WebhookEventName::CUSTODY_SENT, $catalog->items[0]->name);
        self::assertSame(WebhookEventName::CUSTODY_SENT, $catalog->items[0]->name);

        $this->transport->willAnswer(200, self::json(self::TYPE));
        $type = $this->webhooks->catalogItem('wh-1');
        self::assertSame('https://api.test/webhook/wh-1', $this->transport->last()['url']);
        self::assertSame('wh-1', $type->uuid);
        self::assertSame('ENABLED', $type->state);
        self::assertSame('{"fr":"Envoi","en":"Sent"}', $type->label);
        self::assertSame('{}', $type->data);
    }

    public function testTheEventNamesOfTheContract(): void
    {
        self::assertCount(33, WebhookEventName::VALUES);
        self::assertSame('user.identity.step.validated', WebhookEventName::USER_IDENTITY_STEP_VALIDATED);
        self::assertSame('organization.identity.request', WebhookEventName::ORGANIZATION_IDENTITY_REQUEST);
        self::assertSame('alert.status', WebhookEventName::ALERT_STATUS);
    }

    public function testApiErrorsBecomeBitgenExceptions(): void
    {
        $cases = [
            [400, 'webhook_security_https_required', fn () => $this->webhooks->activate('http://example.com')],
            [404, 'unknown_webhook_security', fn () => $this->webhooks->regenerate()],
            [404, 'unknown_webhook_subscriber', fn () => $this->webhooks->archive('sub-x')],
            [404, 'unknown_webhook', fn () => $this->webhooks->catalogItem('wh-x')],
            [409, 'webhook_subscription_already_exists', fn () => $this->webhooks->subscribe(WebhookEventName::CUSTODY_SENT)],
            [429, 'webhook_security_already_enabled', fn () => $this->webhooks->activate('https://example.com')],
            [403, 'forbidden_permission', fn () => $this->webhooks->list()],
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
        foreach ([fn () => $this->webhooks->archive(''), fn () => $this->webhooks->reactivate('..'), fn () => $this->webhooks->logs('.'), fn () => $this->webhooks->catalogItem('')] as $call) {
            try {
                $call();
                self::fail('expected an InvalidArgumentException');
            } catch (InvalidArgumentException) {
            }
        }
        self::assertSame([], $this->transport->requests);
    }

    public function testModelsAreAcceptedForTheSubscriptionAndTheEvent(): void
    {
        $this->transport->willAnswer(200, self::json(self::SUBSCRIPTIONS));
        $subscription = $this->webhooks->list()->items[0];
        $this->transport->willAnswer(200, '[]');
        $this->webhooks->archive($subscription);
        self::assertSame('https://api.test/webhooks/sub-1', $this->transport->last()['url']);
        $this->transport->willAnswer(201, '[]');
        $this->webhooks->reactivate($subscription);
        self::assertSame('https://api.test/webhooks/sub-1', $this->transport->last()['url']);
        $this->transport->willAnswer(200, self::json(['count' => 0, 'items' => []]));
        $this->webhooks->logs($subscription);
        self::assertSame('https://api.test/webhooks/sub-1/logs', $this->transport->last()['url']);

        $this->transport->willAnswer(200, self::json(self::TYPE));
        $this->webhooks->catalogItem($subscription->webhook);
        self::assertSame('https://api.test/webhook/wh-1', $this->transport->last()['url']);
        $this->transport->willAnswer(201, '{"uuid":"sub-9"}');
        $this->webhooks->subscribe($subscription->webhook);   // an event of the catalogue, by model: its uuid is sent
        self::assertSame('{"organization":"org-uuid","event":"wh-1"}', $this->transport->last()['body']);

        $sent = count($this->transport->requests);
        self::assertTypeError(fn () => $this->webhooks->archive($subscription->webhook)); // @phpstan-ignore argument.type
        self::assertTypeError(fn () => $this->webhooks->catalogItem($subscription)); // @phpstan-ignore argument.type
        self::assertCount($sent, $this->transport->requests);
    }
}
