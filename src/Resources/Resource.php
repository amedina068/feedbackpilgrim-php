<?php

declare(strict_types=1);

namespace FeedbackPilgrim\Resources;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use FeedbackPilgrim\Http\Transport;

/**
 * The parent of each API resource.
 *
 * @internal
 */
abstract readonly class Resource
{
    public function __construct(protected Transport $transport) {}

    /**
     * Remove the null values, so that the instance keeps the current value of
     * each field that the caller did not give. The API sends `true` and
     * `false` in a query string as words, which Laravel reads correctly.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected static function filled(array $data): array
    {
        return array_filter($data, static fn (mixed $value): bool => $value !== null);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    protected static function query(array $query): array
    {
        return array_map(
            static fn (mixed $value): mixed => is_bool($value) ? ($value ? 'true' : 'false') : $value,
            self::filled($query),
        );
    }

    /**
     * @param  array<int>|null  $ids
     * @return list<int>|null
     */
    protected static function ids(?array $ids): ?array
    {
        return $ids === null ? null : array_values(array_map('intval', $ids));
    }

    protected static function date(DateTimeInterface|string|null $date): ?string
    {
        if (! $date instanceof DateTimeInterface) {
            return $date;
        }

        return DateTimeImmutable::createFromInterface($date)->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
    }
}
