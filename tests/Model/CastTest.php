<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Tests\Model;

use Bitgen\Sdk\Model\Cast;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

final class CastTest extends TestCase
{
    public function testScalarsAreCastAndMissingOnesGetTheEmptyValue(): void
    {
        $data = ['s' => 'text', 'n' => 12, 'f' => 1.5, 'numeric' => '7', 'b' => true, 'nothing' => null, 'list' => [1, 2]];
        self::assertSame('text', Cast::string($data, 's'));
        self::assertSame('12', Cast::string($data, 'n'));
        self::assertSame('', Cast::string($data, 'missing'));
        self::assertSame('', Cast::string($data, 'list'));
        self::assertSame('text', Cast::nullableString($data, 's'));
        self::assertNull(Cast::nullableString($data, 'nothing'));
        self::assertNull(Cast::nullableString($data, 'missing'));
        self::assertSame(12, Cast::int($data, 'n'));
        self::assertSame(7, Cast::int($data, 'numeric'));
        self::assertSame(1, Cast::int($data, 'f'));
        self::assertSame(0, Cast::int($data, 'missing'));
        self::assertSame(0, Cast::int($data, 's'));
        self::assertSame(12, Cast::nullableInt($data, 'n'));
        self::assertNull(Cast::nullableInt($data, 'missing'));
        self::assertSame(1.5, Cast::float($data, 'f'));
        self::assertSame(12.0, Cast::float($data, 'n'));
        self::assertSame(0.0, Cast::float($data, 'missing'));
        self::assertSame(1.5, Cast::nullableFloat($data, 'f'));
        self::assertNull(Cast::nullableFloat($data, 'nothing'));
        self::assertTrue(Cast::bool($data, 'b'));
        self::assertFalse(Cast::bool($data, 'missing'));
        self::assertSame([1, 2], Cast::raw($data, 'list'));
        self::assertNull(Cast::raw($data, 'missing'));
    }

    public function testObjectsAndLists(): void
    {
        $data = ['o' => ['a' => 1, 0 => 'zero'], 'l' => ['x', ['uuid' => 'u'], 3], 'nope' => 'text'];
        self::assertSame(['a' => 1, '0' => 'zero'], Cast::object($data, 'o'));
        self::assertSame([], Cast::object($data, 'nope'));
        self::assertSame([], Cast::object($data, 'missing'));
        self::assertSame(['a' => 1, '0' => 'zero'], Cast::nullableObject($data, 'o'));
        self::assertNull(Cast::nullableObject($data, 'missing'));
        self::assertSame(['x', ['uuid' => 'u'], 3], Cast::list($data, 'l'));
        self::assertSame([], Cast::list($data, 'nope'));
        // objects(): only the items that are objects are mapped
        self::assertSame(['u'], Cast::objects($data, 'l', static fn (array $item): string => is_string($item['uuid']) ? $item['uuid'] : '?'));
        self::assertSame(['0' => 'a', '1' => 'b'], Cast::asObject(['a', 'b']));
        self::assertSame([], Cast::asObject('text'));
    }

    public function testStringMapKeepsScalarsOnly(): void
    {
        self::assertSame(['fr' => 'Taux', 'en' => 'Rate', 'n' => '1'], Cast::stringMap(['label' => ['fr' => 'Taux', 'en' => 'Rate', 'n' => 1, 'x' => ['nested'], 'y' => null]], 'label'));
        self::assertSame([], Cast::stringMap(['label' => 'text'], 'label'));
        self::assertSame([], Cast::stringMap([], 'label'));
    }

    public function testAnswerListMustBeAListOfObjects(): void
    {
        self::assertSame([], Cast::answerList([]));
        self::assertSame([['a' => 1], ['b' => 2]], Cast::answerList([['a' => 1], ['b' => 2]]));
        foreach ([null, 'text', ['a' => 1], [['a' => 1], 'text']] as $value) {
            try {
                Cast::answerList($value);
                self::fail('expected an UnexpectedValueException');
            } catch (UnexpectedValueException $e) {
                self::assertStringContainsString('the API answered', $e->getMessage());
            }
        }
    }

    public function testAnswerMustBeAnObject(): void
    {
        self::assertSame(['a' => 1], Cast::answer(['a' => 1]));
        foreach ([null, 'text', 12, true] as $value) {
            try {
                Cast::answer($value);
                self::fail('expected an UnexpectedValueException');
            } catch (UnexpectedValueException $e) {
                self::assertStringContainsString('instead of a JSON object', $e->getMessage());
            }
        }
    }
}
