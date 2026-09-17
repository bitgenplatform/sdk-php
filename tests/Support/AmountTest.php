<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Tests\Support;

use Bitgen\Sdk\Support\Amount;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AmountTest extends TestCase
{
    #[DataProvider('accepted')]
    public function testAcceptedAmountsReachTheApiAsStrings(string|int|float $value, string $expected): void
    {
        self::assertSame($expected, Amount::normalize($value));
    }

    /** @return iterable<string, array{string|int|float, string}> */
    public static function accepted(): iterable
    {
        yield '18 decimals, intact' => ['0.000000000000000001', '0.000000000000000001'];
        yield 'string as is' => ['25.00', '25.00'];
        yield 'string trimmed' => [' 1.5 ', '1.5'];
        yield 'exponent string is sent as is (the API decides)' => ['1e-8', '1e-8'];
        yield 'int' => [25, '25'];
        yield 'zero int' => [0, '0'];
        yield 'float' => [0.5, '0.5'];
        yield 'float with many digits' => [123456.789, '123456.789'];
        yield 'whole float' => [25.0, '25'];
        yield 'zero float' => [0.0, '0'];
        yield 'negative zero float' => [-0.0, '0'];
        yield 'smallest decimal float' => [0.0001, '0.0001'];
        yield 'large float, still decimal' => [1234567890123456.0, '1234567890123456'];
    }

    #[DataProvider('refused')]
    public function testRefusedAmountsThrowBeforeAnyRequest(string|int|float $value, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches($message);
        Amount::normalize($value);
    }

    /** @return iterable<string, array{string|int|float, string}> */
    public static function refused(): iterable
    {
        yield 'empty string' => ['', '/^amount must be a non-empty string/'];
        yield 'blank string' => ['   ', '/^amount must be a non-empty string/'];
        yield 'negative int' => [-1, '/^amount must be a non-empty string/'];
        yield 'negative float' => [-0.5, '/^amount must be a non-empty string/'];
        yield 'infinite' => [INF, '/^amount must be a non-empty string/'];
        yield 'nan' => [NAN, '/^amount must be a non-empty string/'];
        yield 'tiny float, exponent notation' => [1e-8, '/would be sent in exponent notation/'];
        yield 'below 1e-4, exponent notation' => [0.00001, '/would be sent in exponent notation/'];
        yield 'huge float, exponent notation' => [1e21, '/would be sent in exponent notation/'];
    }
}
