<?php

declare(strict_types=1);

namespace FeedbackPilgrim\Data;

use DateTimeImmutable;

/**
 * A customer testimonial.
 */
final readonly class Testimonial
{
    /**
     * @param  DateTimeImmutable|null  $approvedAt  Null when the testimonial is pending.
     * @param  list<array<array-key, mixed>>  $votes
     * @param  list<array<array-key, mixed>>  $comments
     * @param  array<array-key, mixed>  $attributes  The decoded JSON object, with every field.
     */
    public function __construct(
        public int $id,
        public string $description,
        public ?int $rating = null,
        public ?string $username = null,
        public ?string $title = null,
        public ?string $subtitle = null,
        public ?string $avatarImage = null,
        public ?int $contactId = null,
        public ?Contact $contact = null,
        public ?DateTimeImmutable $approvedAt = null,
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
            description: $payload->nullableString('description') ?? '',
            rating: $payload->nullableInt('rating'),
            username: $payload->nullableString('username'),
            title: $payload->nullableString('title'),
            subtitle: $payload->nullableString('subtitle'),
            avatarImage: $payload->nullableString('avatar_image'),
            contactId: $payload->nullableInt('contact_id'),
            contact: Contact::nullable($payload->nullableObject('contact')),
            approvedAt: $payload->date('approved_at'),
            votes: $payload->objects('votes'),
            comments: $payload->objects('comments'),
            createdAt: $payload->date('created_at'),
            updatedAt: $payload->date('updated_at'),
            attributes: $data,
        );
    }

    public function isApproved(): bool
    {
        return $this->approvedAt !== null;
    }
}
