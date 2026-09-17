<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Tests;

use TypeError;

/**
 * A wrong model passed where the SDK expects another one is a native TypeError at the call — asserted through a
 * closure so that static analysis, which already flags the call, does not see the catch as dead.
 */
trait TypeErrors
{
    private static function assertTypeError(callable $call): void
    {
        try {
            $call();
        } catch (TypeError $e) {
            self::assertStringContainsString('must be of type', $e->getMessage());

            return;
        }
        self::fail('expected a TypeError');
    }
}
