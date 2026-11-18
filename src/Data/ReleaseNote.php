<?php

declare(strict_types=1);

namespace FeedbackPilgrim\Data;

use DateTimeImmutable;

/**
 * A changelog entry.
 */
final readonly class ReleaseNote
{
    /**
     * @param  string  $content  Markdown.
     * @param  DateTimeImmutable|null  $publishedAt  Null when the note is a draft.
     * @param  list<Tag>  $tags
     * @param  list<array<array-key, mixed>>  $votes  Only in the list response and in the response for one note.
     * @param  list<array<array-key, mixed>>  $comments  Only in the list response and in the response for one note.
     * @param  array<array-key, mixed>  $attributes  The decoded JSON object, with every field.
     */
    public function __construct(
        public int $id,
        public string $title,
        public string $content,
        public ?string $slug = null,
        public ?int $userId = null,
        public ?DateTimeImmutable $publishedAt = null,
        public array $tags = [],
        public array $votes = [],
        public array $comments = [],
        public ?DateTimeImmutable $createdAt = null,
        public ?DateTimeImmutable $updatedAt = null,
        public array $attributes = [],
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
            content: $payload->nullableString('content') ?? '',
            slug: $payload->nullableString('slug'),
            userId: $payload->nullableInt('user_id'),
            publishedAt: $payload->date('published_at'),
            tags: Tag::many($payload->objects('tags')),
            votes: $payload->objects('votes'),
            comments: $payload->objects('comments'),
            createdAt: $payload->date('created_at'),
            updatedAt: $payload->date('updated_at'),
            attributes: $data,
        );
    }

    public function isPublished(): bool
    {
        return $this->publishedAt !== null;
    }
}
