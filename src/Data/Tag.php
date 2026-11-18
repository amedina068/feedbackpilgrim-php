<?php

declare(strict_types=1);

namespace FeedbackPilgrim\Data;

use DateTimeImmutable;

/**
 * A label for posts, release notes and contacts.
 */
final readonly class Tag
{
    /**
     * @param  int|null  $postsCount  Only in the list response.
     * @param  int|null  $contactsCount  Only in the list response.
     */
    public function __construct(
        public int $id,
        public string $title,
        public ?int $postsCount = null,
        public ?int $contactsCount = null,
        public ?DateTimeImmutable $createdAt = null,
        public ?DateTimeImmutable $updatedAt = null,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $payload = Payload::of($data);

        return new self(
            id: $payload->int('id'),
            title: $payload->string('title'),
            postsCount: $payload->nullableInt('posts_count'),
            contactsCount: $payload->nullableInt('contacts_count'),
            createdAt: $payload->date('created_at'),
            updatedAt: $payload->date('updated_at'),
        );
    }

    /**
     * @param  list<array<array-key, mixed>>  $tags
     * @return list<self>
     */
    public static function many(array $tags): array
    {
        return array_map(self::fromArray(...), $tags);
    }
}
