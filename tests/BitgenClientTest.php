<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Tests;

use Bitgen\Sdk\BitgenClient;
use Bitgen\Sdk\Env;
use Bitgen\Sdk\Resource\ApikeysResource;
use Bitgen\Sdk\Resource\AssetResource;
use Bitgen\Sdk\Resource\BankResource;
use Bitgen\Sdk\Resource\CoreResource;
use Bitgen\Sdk\Resource\CustodyResource;
use Bitgen\Sdk\Resource\CustomerResource;
use Bitgen\Sdk\Resource\StakingResource;
use Bitgen\Sdk\Resource\TradingResource;
use Bitgen\Sdk\Resource\TransactionResource;
use Bitgen\Sdk\Resource\WebhooksResource;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BitgenClientTest extends TestCase
{
    public function testValidConfigurationsBuild(): void
    {
        $client = new BitgenClient(scope: 'org-uuid', apiKey: 'k');
        self::assertInstanceOf(CustomerResource::class, $client->customer);
        self::assertInstanceOf(BankResource::class, $client->bank);
        self::assertInstanceOf(CustodyResource::class, $client->custody);
        self::assertInstanceOf(TradingResource::class, $client->trading);
        self::assertInstanceOf(TransactionResource::class, $client->transaction);
        self::assertInstanceOf(StakingResource::class, $client->staking);
        self::assertInstanceOf(CoreResource::class, $client->core);
        self::assertInstanceOf(WebhooksResource::class, $client->webhooks);
        self::assertInstanceOf(ApikeysResource::class, $client->apikeys);
        self::assertInstanceOf(AssetResource::class, $client->asset);
        self::assertInstanceOf(BitgenClient::class, new BitgenClient(scope: 'org-uuid', apiKey: 'k', env: Env::SANDBOX, timeout: 0));
        self::assertInstanceOf(BitgenClient::class, new BitgenClient(scope: 'org-uuid', apiKey: 'k', env: Env::LOCALHOST, port: 4000, timeout: 0.5));
        self::assertInstanceOf(BitgenClient::class, new BitgenClient(scope: 'org-uuid', apiKey: 'k', host: 'my-hostname', port: 8080, isSsl: false));
    }

    /** @param array<string, mixed> $config */
    #[DataProvider('invalidConfigurations')]
    public function testInvalidConfigurationsThrowBeforeAnyRequest(array $config, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches($message);
        new BitgenClient(...$config); // @phpstan-ignore argument.type
    }

    /** @return iterable<string, array{array<string, mixed>, string}> */
    public static function invalidConfigurations(): iterable
    {
        yield 'empty scope' => [['scope' => '', 'apiKey' => 'k'], '/^scope must be a non-empty string$/'];
        yield 'empty key' => [['scope' => 's', 'apiKey' => ''], '/^apiKey must be a non-empty string$/'];
        yield 'non-ascii scope' => [['scope' => "org\n", 'apiKey' => 'k'], '/^scope contains invalid characters/'];
        yield 'non-ascii key' => [['scope' => 's', 'apiKey' => "clé"], '/^apiKey contains invalid characters/'];
        yield 'host with scheme' => [['scope' => 's', 'apiKey' => 'k', 'host' => 'https://api'], '/^host must be a bare hostname/'];
        yield 'host with userinfo' => [['scope' => 's', 'apiKey' => 'k', 'host' => 'me@attacker'], '/^host must be a bare hostname/'];
        yield 'host with query' => [['scope' => 's', 'apiKey' => 'k', 'host' => 'api?x'], '/^host must be a bare hostname/'];
        yield 'host with port' => [['scope' => 's', 'apiKey' => 'k', 'host' => 'api:80'], '/^host must be a bare hostname/'];
        yield 'empty host' => [['scope' => 's', 'apiKey' => 'k', 'host' => ''], '/^host must be a bare hostname/'];
        yield 'port 0' => [['scope' => 's', 'apiKey' => 'k', 'port' => 0], '/^port must be an integer between 1 and 65535$/'];
        yield 'port 70000' => [['scope' => 's', 'apiKey' => 'k', 'host' => 'h', 'port' => 70000], '/^port must be an integer between 1 and 65535$/'];
        yield 'negative timeout' => [['scope' => 's', 'apiKey' => 'k', 'timeout' => -1], '/^timeout must be a number of seconds between 0 \(no timeout\) and 2147483$/'];
        yield 'infinite timeout' => [['scope' => 's', 'apiKey' => 'k', 'timeout' => INF], '/^timeout must be a number of seconds/'];
        yield 'nan timeout' => [['scope' => 's', 'apiKey' => 'k', 'timeout' => NAN], '/^timeout must be a number of seconds/'];
        yield 'too large timeout' => [['scope' => 's', 'apiKey' => 'k', 'timeout' => 2_147_484], '/^timeout must be a number of seconds/'];
    }

    public function testInvalidValuesAreNeverEchoed(): void
    {
        foreach (['scope' => ['scope' => "SECRET-SCOPE\n", 'apiKey' => 'k'], 'apiKey' => ['scope' => 's', 'apiKey' => "SECRET-KEY\n"]] as $config) {
            try {
                new BitgenClient(...$config);
                self::fail('expected an InvalidArgumentException');
            } catch (InvalidArgumentException $e) {
                self::assertStringNotContainsString('SECRET', $e->getMessage());
            }
        }
    }

    public function testTheClientAndTheResourcesCanBeMocked(): void
    {
        // integrators double them in their own tests: no `final`
        self::assertFalse((new \ReflectionClass(BitgenClient::class))->isFinal());
        $resources = glob(__DIR__ . '/../src/Resource/*Resource.php') ?: [];
        self::assertNotSame([], $resources);
        foreach ($resources as $file) {
            /** @var class-string $class */
            $class = 'Bitgen\\Sdk\\Resource\\' . basename($file, '.php');
            self::assertFalse((new \ReflectionClass($class))->isFinal(), $class);
        }
        $mock = $this->createMock(BitgenClient::class);
        self::assertInstanceOf(BitgenClient::class, $mock);
        $resource = $this->createMock(AssetResource::class);
        self::assertInstanceOf(AssetResource::class, $resource);
    }

    public function testEnvMustBeOneOfTheEnvConstants(): void
    {
        self::assertSame(['production', 'sandbox', 'staging', 'localhost'], Env::VALUES);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('env must be production, sandbox, staging or localhost');
        new BitgenClient(scope: 's', apiKey: 'k', env: 'Sandbox');
    }
}
