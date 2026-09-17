<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Tests;

use Bitgen\Sdk\Version;
use PHPUnit\Framework\TestCase;

final class VersionTest extends TestCase
{
    public function testTheVersionIsSemver(): void
    {
        self::assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', Version::VERSION);
    }
}
