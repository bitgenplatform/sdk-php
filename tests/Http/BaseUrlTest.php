<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Tests\Http;

use Bitgen\Sdk\Env;
use Bitgen\Sdk\Http\BaseUrl;
use Bitgen\Sdk\Http\Timeout;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class BaseUrlTest extends TestCase
{
    public function testHostPerEnvironment(): void
    {
        self::assertSame('https://api.bitgen.com', BaseUrl::resolve(Env::PRODUCTION, null, null, true));
        self::assertSame('https://api.sandbox.bitgen.com', BaseUrl::resolve(Env::SANDBOX, null, null, true));
        self::assertSame('https://api.staging.btgn.dev', BaseUrl::resolve(Env::STAGING, null, null, true));
        self::assertSame('http://localhost:3002', BaseUrl::resolve(Env::LOCALHOST, null, null, true));
        self::assertSame('http://localhost:4000', BaseUrl::resolve(Env::LOCALHOST, null, 4000, true));
        try {
            BaseUrl::resolve('prod', null, null, true);
            self::fail('expected an InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            self::assertSame('env must be production, sandbox, staging or localhost', $e->getMessage());
        }
        // port and isSsl are ignored with the hosted environments
        self::assertSame('https://api.sandbox.bitgen.com', BaseUrl::resolve(Env::SANDBOX, null, 8080, false));
    }

    public function testCustomHost(): void
    {
        self::assertSame('https://my-hostname:80', BaseUrl::resolve(Env::PRODUCTION, 'my-hostname', null, true));
        self::assertSame('http://my-hostname:8080', BaseUrl::resolve(Env::SANDBOX, 'my-hostname', 8080, false));
        self::assertSame('http://10.0.0.7:3002', BaseUrl::resolve(Env::SANDBOX, '10.0.0.7', 3002, false));
        self::assertSame('https://api_internal.corp.local:443', BaseUrl::resolve(Env::SANDBOX, 'api_internal.corp.local', 443, true));
    }

    public function testInvalidHostOrPort(): void
    {
        foreach (['https://api', 'api:80', 'api/v4', 'a b', '', 'me@host', 'host?x=1', 'host#f', '[::1]'] as $host) {
            try {
                BaseUrl::resolve(Env::PRODUCTION, $host, null, true);
                self::fail('expected an InvalidArgumentException for host ' . $host);
            } catch (InvalidArgumentException $e) {
                self::assertStringStartsWith('host must be a bare hostname', $e->getMessage());
            }
        }
        foreach ([0, -1, 65536] as $port) {
            try {
                BaseUrl::resolve(Env::LOCALHOST, null, $port, true);
                self::fail('expected an InvalidArgumentException for port ' . $port);
            } catch (InvalidArgumentException $e) {
                self::assertSame('port must be an integer between 1 and 65535', $e->getMessage());
            }
        }
    }

    public function testTimeoutSecondsToWholeMilliseconds(): void
    {
        self::assertSame(30_000, Timeout::toMilliseconds(Timeout::DEFAULT));
        self::assertSame(0, Timeout::toMilliseconds(0));
        self::assertSame(0, Timeout::toMilliseconds(0.0));
        self::assertSame(100, Timeout::toMilliseconds(0.1));
        self::assertSame(300, Timeout::toMilliseconds(0.3));       // not 300.00000000000006: curl wants an integer
        self::assertSame(1, Timeout::toMilliseconds(0.0004));      // a positive timeout never rounds down to "none"
        self::assertSame(2_147_483_000, Timeout::toMilliseconds(Timeout::MAX));
        foreach ([-1, -0.5, INF, NAN, Timeout::MAX + 1] as $bad) {
            try {
                Timeout::toMilliseconds($bad);
                self::fail('expected an InvalidArgumentException');
            } catch (InvalidArgumentException $e) {
                self::assertSame('timeout must be a number of seconds between 0 (no timeout) and 2147483', $e->getMessage());
            }
        }
    }
}
