<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Tests\Support;

use Bitgen\Sdk\Support\Path;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PathTest extends TestCase
{
    public function testSegmentsAreEncoded(): void
    {
        self::assertSame('eth', Path::segment('eth', 'asset'));
        self::assertSame('ed1a19bb-1', Path::segment('ed1a19bb-1', 'user'));
        self::assertSame('jean%40valjean.fr', Path::segment('jean@valjean.fr', 'user'));
        self::assertSame('a%2Fb%3Fc%23d%20e', Path::segment('a/b?c#d e', 'reference'));
    }

    public function testEmptyAndDotSegmentsAreRefused(): void
    {
        foreach (['', '  '] as $value) {
            try {
                Path::segment($value, 'asset');
                self::fail('expected an InvalidArgumentException');
            } catch (InvalidArgumentException $e) {
                self::assertSame('asset must be a non-empty string', $e->getMessage());
            }
        }
        foreach (['.', '..'] as $value) {
            try {
                Path::segment($value, 'user');
                self::fail('expected an InvalidArgumentException');
            } catch (InvalidArgumentException $e) {
                self::assertSame('user must not be "." or ".."', $e->getMessage());
            }
        }
    }
}
