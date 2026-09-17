<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Tests\Model;

use Bitgen\Sdk\Model\History;
use PHPUnit\Framework\TestCase;

final class HistoryTest extends TestCase
{
    public function testPointsAreEpochAndValuePairs(): void
    {
        $history = History::fromArray(['d' => [[1700000000, 1.5], ['1700003600', '2']], 'w' => 'nope', 'y' => [[1]], 'extra' => []]);
        self::assertSame([[1700000000, 1.5], [1700003600, 2.0]], $history->d);
        self::assertSame([], $history->w);
        self::assertSame([], $history->m);
        self::assertSame([], $history->y);
        self::assertSame([], $history->all);
    }
}
