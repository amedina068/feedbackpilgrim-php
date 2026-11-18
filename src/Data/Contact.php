<?php

declare(strict_types=1);

namespace FeedbackPilgrim\Data;

use DateTimeImmutable;

/**
 * A customer of the client.
 *
 * The Contacts operations send each custom field as a field of the contact.
 * A contact inside another record, for example the contact of a post, sends
 * the custom fields as one JSON string in `metadata`. The SDK reads both
 * forms into `$customFields`.
 */
final readonly class Contact
{
    /**
     * The fields of the contact record. Each other field of a contact from the
     * Contacts operations is a custom field.
     */
    private const FIELDS = [
        'id', 'uuid', 'user_id', 'client_id', 'name', 'email', 'notes', 'avatar', 'mrr', 'ltv',
        'top_customer', 'metadata', 'created_at', 'updated_at', 'tags', 'votes',
    ];

    /**
     * @param  array<string, mixed>  $customFields
     * @param  list<Tag>  $tags
     * @param  list<array<array-key, mixed>>  $votes
     * @param  array<array-key, mixed>  $attributes  The decoded JSON object, with every field.
     */
    public function __construct(
        public int $id,
        public ?string $uuid,
        public ?string $name,
        public ?string $email,
        public ?string $avatar = null,
        public ?float $mrr = null,
        public ?float $ltv = null,
        public bool $topCustomer = false,
        public ?string $notes = null,
        public ?int $userId = null,
        public array $customFields = [],
        public array $tags = [],
        public array $votes = [],
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
            uuid: $payload->nullableString('uuid'),
            name: $payload->nullableString('name'),
            email: $payload->nullableString('email'),
            avatar: $payload->nullableString('avatar'),
            mrr: $payload->nullableFloat('mrr'),
            ltv: $payload->nullableFloat('ltv'),
            topCustomer: $payload->bool('top_customer'),
            notes: $payload->nullableString('notes'),
            userId: $payload->nullableInt('user_id'),
            customFields: self::customFieldsOf($data),
            tags: Tag::many($payload->objects('tags')),
            votes: $payload->objects('votes'),
            createdAt: $payload->date('created_at'),
            updatedAt: $payload->date('updated_at'),
            attributes: $data,
        );
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function nullable(?array $data): ?self
    {
        return $data === null ? null : self::fromArray($data);
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<string, mixed>
     */
    private static function customFieldsOf(array $data): array
    {
        if (! array_key_exists('metadata', $data)) {
            $fields = array_diff_key($data, array_flip(self::FIELDS));

            return self::withStringKeys($fields);
        }

        $metadata = is_string($data['metadata']) ? json_decode($data['metadata'], true) : $data['metadata'];

        if (! is_array($metadata)) {
            return [];
        }

        // The app keeps internal values, for example `pilgrim_last_login`,
        // in the same JSON. They are not custom fields.
        return array_filter(
            self::withStringKeys($metadata),
            static fn (string $key): bool => ! str_starts_with($key, 'pilgrim_'),
            ARRAY_FILTER_USE_KEY,
        );
    }

    /**
     * @param  array<array-key, mixed>  $fields
     * @return array<string, mixed>
     */
    private static function withStringKeys(array $fields): array
    {
        $result = [];

        foreach ($fields as $key => $value) {
            $result[(string) $key] = $value;
        }

        return $result;
    }
}
