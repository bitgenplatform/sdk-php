<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Tests;

use Bitgen\Sdk\Page;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

final class PageTest extends TestCase
{
    public function testMapsEveryItem(): void
    {
        $page = Page::fromArray(['count' => 12, 'items' => [['uuid' => 'a'], ['uuid' => 'b']]], static fn (array $item): string => is_string($item['uuid']) ? $item['uuid'] : '?');
        self::assertSame(12, $page->count);
        self::assertSame(['a', 'b'], $page->items);
    }

    public function testMissingItemsAndCountDefault(): void
    {
        $page = Page::fromArray([], static fn (array $item): array => $item);
        self::assertSame(0, $page->count);
        self::assertSame([], $page->items);
    }

    public function testMalformedPagesAreRefused(): void
    {
        $this->expectException(UnexpectedValueException::class);
        Page::fromArray(['count' => 1, 'items' => 'nope'], static fn (array $item): array => $item);
    }
}
