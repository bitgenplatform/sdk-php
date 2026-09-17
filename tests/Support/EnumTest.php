<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Tests\Support;

use Bitgen\Sdk\Asset;
use Bitgen\Sdk\Env;
use Bitgen\Sdk\Model\BankDirection;
use Bitgen\Sdk\Model\Locale;
use Bitgen\Sdk\Model\OrganizationCategory;
use Bitgen\Sdk\Model\TradingDirection;
use Bitgen\Sdk\Model\WebhookEventName;
use Bitgen\Sdk\Support\Enum;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class EnumTest extends TestCase
{
    public function testAValueOfTheListIsReturnedAsIs(): void
    {
        self::assertSame(Locale::FR, Enum::ensure(Locale::FR, Locale::VALUES, 'locale'));
        self::assertSame(TradingDirection::SELL, Enum::ensure(TradingDirection::SELL, TradingDirection::VALUES, 'direction'));
        self::assertSame(BankDirection::ALL, Enum::ensure(BankDirection::ALL, BankDirection::VALUES, 'direction'));   // the ALL direction, not the list
    }

    public function testAValueOutsideTheListIsRefusedWithTheListInTheMessage(): void
    {
        $cases = [
            ['fr', Locale::VALUES, 'locale', 'locale must be FR or EN'],
            ['BUY', TradingDirection::VALUES, 'direction', 'direction must be buy or sell'],
            ['deposit', BankDirection::VALUES, 'direction', 'direction must be ALL, DEPOSIT, WITHDRAWAL, PURCHASE or SELL'],
            ['Sandbox', Env::VALUES, 'env', 'env must be production, sandbox, staging or localhost'],
            ['', Locale::VALUES, 'locale', 'locale must be FR or EN'],
            ['BUSINESS', OrganizationCategory::VALUES, 'organization', 'organization must be CUSTOMER or B2B'],
        ];
        foreach ($cases as [$value, $values, $name, $message]) {
            try {
                Enum::ensure($value, $values, $name);
                self::fail('expected an InvalidArgumentException');
            } catch (InvalidArgumentException $e) {
                self::assertSame($message, $e->getMessage());   // the list, never the value received
            }
        }
    }

    public function testEveryConstantClassListsItsValuesOnceAndInOrder(): void
    {
        /** @var list<class-string> $classes */
        $classes = [Env::class, Asset::class];
        foreach (glob(__DIR__ . '/../../src/Model/*.php') ?: [] as $file) {
            if (str_contains((string) file_get_contents($file), "\n    public const VALUES = [")) {
                /** @var class-string $class */
                $class = 'Bitgen\\Sdk\\Model\\' . basename($file, '.php');
                $classes[] = $class;
            }
        }
        self::assertCount(25, $classes);   // Env, Asset + the 23 value classes of src/Model/ (mirror of the JS constants)
        foreach ($classes as $class) {
            $reflection = new \ReflectionClass($class);
            self::assertTrue($reflection->isFinal(), $class);
            self::assertFalse($reflection->isInstantiable(), $class);
            $constants = $reflection->getConstants();
            $values = $constants['VALUES'];
            unset($constants['VALUES']);
            self::assertIsArray($values);
            /** @var list<string> $values */
            self::assertSame(array_values($constants), $values, $class . ': VALUES is every constant, in declaration order');
            self::assertSame($values, array_unique($values), $class . ': no duplicate value');
            self::assertContainsOnly('string', $values);
        }
        self::assertCount(33, WebhookEventName::VALUES);
        self::assertSame('btc', Asset::BTC);
        self::assertSame('sandbox', Env::SANDBOX);
    }
}
