<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Tests\Http;

use Bitgen\Sdk\Exception\BitgenException;
use Bitgen\Sdk\Http\CurlTransport;
use Bitgen\Sdk\Http\HttpClient;
use Bitgen\Sdk\Http\TransportException;
use PHPUnit\Framework\TestCase;

/**
 * The real transport against a throwaway `php -S` server (tests/server/router.php).
 */
final class CurlTransportTest extends TestCase
{
    private const KEY = 'SECRET-KEY-NEVER-SHOWN';

    /** @var resource|null */
    private static $server = null;
    private static int $port = 0;

    public static function setUpBeforeClass(): void
    {
        self::$port = self::freePort();
        $command = [PHP_BINARY, '-S', '127.0.0.1:' . self::$port, dirname(__DIR__) . '/server/router.php'];
        // One worker process (no PHP_CLI_SERVER_WORKERS): orphaned workers would outlive proc_terminate and keep the test runner's pipes open
        $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
        if ($process === false) {
            self::fail('cannot start the test server');
        }
        self::$server = $process;
        // wait for the server to answer
        for ($i = 0; $i < 100; $i++) {
            $socket = @fsockopen('127.0.0.1', self::$port, $errno, $error, 0.1);
            if ($socket !== false) {
                fclose($socket);

                return;
            }
            usleep(50_000);
        }
        self::fail('the test server did not start');
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$server !== null) {
            proc_terminate(self::$server);
            proc_close(self::$server);
            self::$server = null;
        }
    }

    private static function freePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
        if ($socket === false) {
            self::fail('cannot find a free port');
        }
        $name = stream_socket_get_name($socket, false);
        fclose($socket);
        if ($name === false) {
            self::fail('cannot read the port');
        }

        return (int) substr($name, (int) strrpos($name, ':') + 1);
    }

    private static function client(int|float $timeoutSeconds = 5, ?int $port = null): HttpClient
    {
        return new HttpClient(new CurlTransport(), 'org-uuid', self::KEY, 'http://127.0.0.1:' . ($port ?? self::$port), (int) ($timeoutSeconds * 1000), 'bitgen-sdk-php/test');
    }

    public function testSendsHeadersMethodAndBodyOverTheWire(): void
    {
        $answer = self::client()->post('/ok', ['amount' => '1.5', 'note' => 'a/b']);
        self::assertIsArray($answer);
        self::assertSame('POST', $answer['method']);
        self::assertSame('{"amount":"1.5","note":"a/b"}', $answer['body']);
        self::assertIsArray($answer['headers']);
        $headers = array_change_key_case($answer['headers'], CASE_LOWER);
        self::assertSame('application/json', $headers['content-type']);
        self::assertSame('application/json', $headers['accept']);
        self::assertSame('bitgen-sdk-php/test', $headers['user-agent']);
        self::assertSame('org-uuid', $headers['bitgen-scope']);
        self::assertSame(self::KEY, $headers['api-key']);
        self::assertArrayNotHasKey('expect', $headers);

        // a body-less write still announces an empty body
        $answer = self::client()->post('/ok');
        self::assertIsArray($answer);
        self::assertSame('', $answer['body']);
        self::assertIsArray($answer['headers']);
        self::assertSame('0', array_change_key_case($answer['headers'], CASE_LOWER)['content-length'] ?? null);

        $answer = self::client()->get('/ok');
        self::assertIsArray($answer);
        self::assertIsArray($answer['headers']);
        self::assertArrayNotHasKey('content-length', array_change_key_case($answer['headers'], CASE_LOWER));
    }

    public function testErrorEmptyAndTextAnswers(): void
    {
        try {
            self::client()->get('/error');
            self::fail('expected a BitgenException');
        } catch (BitgenException $e) {
            self::assertSame(412, $e->status);
            self::assertSame('bank_rib_required', $e->errorCode);
        }
        self::assertNull(self::client()->get('/empty'));
        try {
            self::client()->get('/text');
            self::fail('expected a BitgenException');
        } catch (BitgenException $e) {
            self::assertSame(200, $e->status);
            self::assertSame('plain text', $e->errorCode);
        }
        try {
            self::client()->get('/nowhere');
            self::fail('expected a BitgenException');
        } catch (BitgenException $e) {
            self::assertSame(500, $e->status);
            self::assertSame('Internal Server Error', $e->errorCode);
        }
    }

    public function testRedirectsAreNeverFollowed(): void
    {
        try {
            self::client()->get('/redirect');
            self::fail('expected a BitgenException');
        } catch (BitgenException $e) {
            self::assertSame(302, $e->status);
            self::assertSame('Found', $e->errorCode);
        }
    }

    public function testTimeoutGivesRequestTimeoutWithStatusZero(): void
    {
        try {
            self::client(0.2)->get('/hang');
            self::fail('expected a BitgenException');
        } catch (BitgenException $e) {
            self::assertSame(0, $e->status);
            self::assertSame('request_timeout', $e->errorCode);
            self::assertSame('request_timeout (HTTP 0)', $e->getMessage());
            $previous = $e->getPrevious();
            self::assertInstanceOf(TransportException::class, $previous);
            self::assertSame(CURLE_OPERATION_TIMEDOUT, $previous->getCode());
            self::assertKeyIsNowhere($e);
        }
    }

    /** Last on purpose: the single-worker server is still busy with /hang for a moment after the timeout test */
    public function testClosedPortGivesNetworkError(): void
    {
        try {
            self::client(5, self::freePort())->get('/ok');
            self::fail('expected a BitgenException');
        } catch (BitgenException $e) {
            self::assertSame(0, $e->status);
            self::assertSame('network_error', $e->errorCode);
            self::assertInstanceOf(TransportException::class, $e->getPrevious());
            self::assertKeyIsNowhere($e);
        }
    }

    private static function assertKeyIsNowhere(BitgenException $e): void
    {
        $texts = [$e->getMessage(), (string) $e, $e->getPrevious()?->getMessage() ?? ''];
        foreach ($texts as $text) {
            self::assertStringNotContainsString(self::KEY, $text);
        }
    }
}
