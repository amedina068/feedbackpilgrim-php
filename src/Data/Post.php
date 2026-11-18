<?php

declare(strict_types=1);

namespace FeedbackPilgrim\Data;

use DateTimeImmutable;

/**
 * A feature request or other feedback from a contact.
 */
final readonly class Post
{
    public const PENDING = 'pending';

    public const IN_PROGRESS = 'in_progress';

    public const COMPLETED = 'completed';

    /**
     * @param  string|null  $status  `pending`, `in_progress` or `completed`. The stage of the post sets this value.
     * @param  Stage|null  $stage  Only in the create response and in some update responses.
     * @param  list<Tag>  $tags
     * @param  list<array<array-key, mixed>>  $votes
     * @param  list<array<array-key, mixed>>  $comments  Only in the response for one post.
     * @param  array<array-key, mixed>  $attributes  The decoded JSON object, with every field.
     */
    public function __construct(
        public int $id,
        public string $title,
        public ?string $content,
        public ?string $status,
        public ?int $boardId = null,
        public ?int $stageId = null,
        public ?int $contactId = null,
        public ?string $notes = null,
        public ?Contact $contact = null,
        public ?Stage $stage = null,
        public array $tags = [],
        public array $votes = [],
        public array $comments = [],
        public ?DateTimeImmutable $publishedAt = null,
        public ?DateTimeImmutable $archivedAt = null,
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
        $stage = $payload->nullableObject('stage');

        return new self(
            id: $payload->int('id'),
            title: $payload->string('title'),
            content: $payload->nullableString('content'),
            status: $payload->nullableString('status'),
            boardId: $payload->nullableInt('board_id'),
            stageId: $payload->nullableInt('stage_id'),
            contactId: $payload->nullableInt('contact_id'),
            notes: $payload->nullableString('notes'),
            contact: Contact::nullable($payload->nullableObject('contact')),
            stage: $stage === null ? null : Stage::fromArray($stage),
            tags: Tag::many($payload->objects('tags')),
            votes: $payload->objects('votes'),
            comments: $payload->objects('comments'),
            publishedAt: $payload->date('published_at'),
            archivedAt: $payload->date('archived_at'),
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
