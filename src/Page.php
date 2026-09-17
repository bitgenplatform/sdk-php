<?php

declare(strict_types=1);

namespace Bitgen\Sdk;

use UnexpectedValueException;

/**
 * A paginated list of the API: `{ count, items }`.
 *
 * @template T
 */
final readonly class Page
{
    /**
     * @param list<T> $items
     */
    public function __construct(
        public int $count,
        public array $items,
    ) {
    }

    /**
     * Builds a page from the decoded answer, mapping every item with `$item`. A missing `items` is an empty
     * page, a missing `count` the number of items; anything that is not a list of objects is a contract violation.
     *
     * @template U
     *
     * @param array<mixed>                         $data
     * @param callable(array<string, mixed>): U    $item
     *
     * @return self<U>
     *
     * @throws UnexpectedValueException
     */
    public static function fromArray(array $data, callable $item): self
    {
        $rawItems = $data['items'] ?? [];
        if (!is_array($rawItems)) {
            throw new UnexpectedValueException('the API answered a page whose items are not a list');
        }
        $items = [];
        foreach ($rawItems as $raw) {
            if (!is_array($raw)) {
                throw new UnexpectedValueException('the API answered a page with an item that is not an object');
            }
            /** @var array<string, mixed> $raw */
            $items[] = $item($raw);
        }
        $count = $data['count'] ?? count($items);
        if (!is_int($count)) {
            throw new UnexpectedValueException('the API answered a page whose count is not an integer');
        }

        return new self($count, $items);
    }
}
