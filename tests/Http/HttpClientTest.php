<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Tests\Http;

use Bitgen\Sdk\Exception\BitgenException;
use Bitgen\Sdk\Http\HttpClient;
use Bitgen\Sdk\Http\TransportException;
use Bitgen\Sdk\Version;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class HttpClientTest extends TestCase
{
    private FakeTransport $transport;
    private HttpClient $http;

    protected function setUp(): void
    {
        $this->transport = new FakeTransport();
        $this->http = new HttpClient($this->transport, 'org-uuid', 'SECRET-KEY', 'https://api.example.test', 30_000, 'bitgen-sdk-php/' . Version::VERSION);
    }

    public function testSendsTheExactHeadersAndUrl(): void
    {
        $this->transport->willAnswer(200, '{"ok":true}');
        self::assertSame(['ok' => true], $this->http->get('/asset'));

        $request = $this->transport->last();
        self::assertSame('GET', $request['method']);
        self::assertSame('https://api.example.test/asset', $request['url']);
        self::assertSame([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => 'bitgen-sdk-php/' . Version::VERSION,
            'BITGEN-Scope' => 'org-uuid',
            'Api-key' => 'SECRET-KEY',
        ], $request['headers']);
        self::assertNull($request['body']);
        self::assertSame(30_000, $request['timeoutMs']);
        self::assertSame('org-uuid', $this->http->scope);
    }

    public function testQueryStringSkipsNullsAndWritesBooleansAsWords(): void
    {
        $this->http->get('/customer', ['offset' => 0, 'limit' => 50, 'includeClosed' => true, 'strict' => false, 'manager' => null, 'q' => 'a b&c']);
        self::assertSame('https://api.example.test/customer?offset=0&limit=50&includeClosed=true&strict=false&q=a%20b%26c', $this->transport->last()['url']);

        $this->http->get('/customer', ['manager' => null]);
        self::assertSame('https://api.example.test/customer', $this->transport->last()['url']);
    }

    public function testBodiesAreJsonWithUnescapedSlashesAndAnEmptyArrayIsAnObject(): void
    {
        $this->transport->willAnswer(201, '{"uuid":"u"}');
        self::assertSame(['uuid' => 'u'], $this->http->post('/bank', ['amount' => '100.00', 'message' => 'BTGN/42', 'user' => null]));
        self::assertSame('POST', $this->transport->last()['method']);
        self::assertSame('{"amount":"100.00","message":"BTGN/42","user":null}', $this->transport->last()['body']);

        $this->http->put('/staking/p/rewards', []);
        self::assertSame('{}', $this->transport->last()['body']);

        $this->http->patch('/webhook/security/o/regenerate');
        self::assertSame('PATCH', $this->transport->last()['method']);
        self::assertNull($this->transport->last()['body']);

        $this->http->delete('/webhooks/s');
        self::assertSame('DELETE', $this->transport->last()['method']);
    }

    public function testEmptyBodyAnd204GiveNull(): void
    {
        $this->transport->willAnswer(204, 'ignored');
        self::assertNull($this->http->post('/webhooks/s'));
        $this->transport->willAnswer(200, '  ');
        self::assertNull($this->http->get('/x'));
        $this->transport->willAnswer(200, '[]');
        self::assertSame([], $this->http->put('/account/u', ['action' => []]));
    }

    public function testJsonErrorBodyBecomesABitgenException(): void
    {
        $this->transport->willAnswer(416, '{"error":true,"message":"requested_amount_error","code":416}');
        try {
            $this->http->put('/bank/u', ['amount' => '50']);
            self::fail('expected a BitgenException');
        } catch (BitgenException $e) {
            self::assertSame(416, $e->status);
            self::assertSame(416, $e->getCode());
            self::assertSame('requested_amount_error', $e->errorCode);
            self::assertSame('requested_amount_error (HTTP 416)', $e->getMessage());
            self::assertNull($e->getPrevious());
        }
    }

    #[DataProvider('rawBodies')]
    public function testNonJsonEmptyAndRedirectAnswersKeepTheRawTextOrTheReason(int $status, string $body, string $reason, string $expected): void
    {
        $this->transport->willAnswer($status, $body, $reason);
        try {
            $this->http->get('/x');
            self::fail('expected a BitgenException');
        } catch (BitgenException $e) {
            self::assertSame($status, $e->status);
            self::assertSame($expected, $e->errorCode);
        }
    }

    /** @return iterable<string, array{int, string, string, string}> */
    public static function rawBodies(): iterable
    {
        yield 'html error page' => [502, "<html>Bad gateway</html>\n", 'Bad Gateway', '<html>Bad gateway</html>'];
        yield 'empty body with a reason' => [404, '', 'Not Found', 'Not Found'];
        yield 'empty body, no reason (HTTP/2)' => [404, '', '', '404'];
        yield 'redirect, never followed' => [302, '', 'Found', 'Found'];
        yield 'json without message' => [500, '{"error":true}', 'Internal Server Error', '{"error":true}'];
        yield 'json with a non-string message' => [500, '{"error":true,"message":123}', 'Internal Server Error', '{"error":true,"message":123}'];
        yield 'json with an empty message' => [500, '{"error":true,"message":""}', 'Internal Server Error', '{"error":true,"message":""}'];
        yield '2xx that is not json' => [200, 'not json at all', 'OK', 'not json at all'];
    }

    public function testErrorCodeIsCappedAt200Characters(): void
    {
        $long = str_repeat('x', 250);
        $this->transport->willAnswer(400, json_encode(['error' => true, 'message' => $long, 'code' => 400], JSON_THROW_ON_ERROR));
        try {
            $this->http->get('/x');
            self::fail('expected a BitgenException');
        } catch (BitgenException $e) {
            self::assertSame(200, strlen($e->errorCode));
        }
        $this->transport->willAnswer(502, str_repeat('é', 250));
        try {
            $this->http->get('/x');
            self::fail('expected a BitgenException');
        } catch (BitgenException $e) {
            self::assertSame(200, preg_match_all('/./us', $e->errorCode));
        }
    }

    public function testNoHttpAnswerBecomesStatusZeroWithTheTransportErrorAsPrevious(): void
    {
        $this->transport->willFail(new TransportException('request_timeout', 'Operation timed out after 100 milliseconds', 28));
        try {
            $this->http->get('/hang');
            self::fail('expected a BitgenException');
        } catch (BitgenException $e) {
            self::assertSame(0, $e->status);
            self::assertSame('request_timeout', $e->errorCode);
            self::assertSame('request_timeout (HTTP 0)', $e->getMessage());
            self::assertInstanceOf(TransportException::class, $e->getPrevious());
        }
        $this->transport->willFail(new TransportException('network_error', 'Could not resolve host: api.example.test', 6));
        try {
            $this->http->get('/x');
            self::fail('expected a BitgenException');
        } catch (BitgenException $e) {
            self::assertSame('network_error', $e->errorCode);
            self::assertStringNotContainsString('SECRET-KEY', $e->getMessage() . ($e->getPrevious()?->getMessage() ?? ''));
        }
    }
}
