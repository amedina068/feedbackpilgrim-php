<?php

declare(strict_types=1);

namespace FeedbackPilgrim\Data;

use ArrayIterator;
use Closure;
use Countable;
use Generator;
use IteratorAggregate;
use Traversable;

/**
 * One page of a list. Each list of the API is a Laravel length-aware
 * paginator with page numbers.
 *
 * @template TItem
 *
 * @implements IteratorAggregate<int, TItem>
 */
final readonly class Page implements Countable, IteratorAggregate
{
    /**
     * @param  list<TItem>  $items
     */
    public function __construct(
        public array $items,
        public int $currentPage,
        public int $lastPage,
        public int $perPage,
        public int $total,
        public ?string $nextPageUrl = null,
    ) {}

    /**
     * @internal
     *
     * Read the body of a Laravel paginator. The paginator fields are at the
     * top level of the body, next to `data`.
     *
     * @template TValue
     *
     * @param  array<array-key, mixed>  $body
     * @param  callable(array<array-key, mixed>): TValue  $map
     * @return self<TValue>
     */
    public static function fromBody(array $body, callable $map): self
    {
        $payload = Payload::of($body);

        return new self(
            array_map($map, $payload->objects('data')),
            $payload->int('current_page'),
            $payload->has('last_page') ? $payload->int('last_page') : $payload->int('current_page'),
            $payload->int('per_page'),
            $payload->has('total') ? $payload->int('total') : 0,
            $payload->nullableString('next_page_url'),
        );
    }

    /**
     * @internal
     *
     * Read each page, from the first page to the last, and yield each item.
     * The SDK reads a page only when the caller reaches it.
     *
     * @template TValue
     *
     * @param  Closure(int): self<TValue>  $page  Reads one page by its number.
     * @return Generator<int, TValue>
     */
    public static function walk(Closure $page): Generator
    {
        $number = 1;

        do {
            $current = $page($number);

            foreach ($current->items as $item) {
                yield $item;
            }

            $number = $current->currentPage + 1;
        } while ($current->hasMore() && $current->items !== []);
    }

    public function hasMore(): bool
    {
        return $this->currentPage < $this->lastPage;
    }

    public function nextPage(): ?int
    {
        return $this->hasMore() ? $this->currentPage + 1 : null;
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /**
     * @return TItem|null
     */
    public function first(): mixed
    {
        return $this->items[0] ?? null;
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return Traversable<int, TItem>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }
}
