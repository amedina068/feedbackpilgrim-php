<?php

declare(strict_types=1);

namespace FeedbackPilgrim\Resources;

use FeedbackPilgrim\Data\Contact;
use FeedbackPilgrim\Data\Page;
use Generator;

/**
 * The customers of the client.
 */
final readonly class Contacts extends Resource
{
    /**
     * One page of contacts, with their tags and votes.
     *
     * @param  string|null  $search  Matches part of the name or the email.
     * @param  string|null  $tag  The exact title of a tag.
     * @param  string|null  $sortBy  `created_at` (default), `updated_at`, `name`, `email`, `mrr` or `ltv`.
     * @param  string|null  $sortDirection  `asc` or `desc` (default).
     * @param  int|null  $perPage  1 to 100. The default is 15.
     * @return Page<Contact>
     */
    public function list(
        ?string $search = null,
        ?string $tag = null,
        ?string $sortBy = null,
        ?string $sortDirection = null,
        ?int $perPage = null,
        int $page = 1,
    ): Page {
        $body = $this->transport->get('contacts', self::query([
            'search' => $search,
            'tag' => $tag,
            'sort_by' => $sortBy,
            'sort_direction' => $sortDirection,
            'per_page' => $perPage,
            'page' => $page === 1 ? null : $page,
        ]))->json();

        return Page::fromBody($body, Contact::fromArray(...));
    }

    /**
     * Every contact that matches the filters, page by page.
     *
     * @return Generator<int, Contact>
     */
    public function all(
        ?string $search = null,
        ?string $tag = null,
        ?string $sortBy = null,
        ?string $sortDirection = null,
        int $perPage = 100,
    ): Generator {
        return Page::walk(fn (int $page): Page => $this->list($search, $tag, $sortBy, $sortDirection, $perPage, $page));
    }

    /**
     * One contact, with its tags and votes.
     */
    public function find(int $id): Contact
    {
        return Contact::fromArray($this->transport->get("contacts/{$id}")->json());
    }

    /**
     * Create a contact, or update the contact of the client with this email.
     *
     * The instance merges the custom fields with the custom fields that the
     * contact has. It accepts 50 keys, 3 levels and 16 KB at most. A key
     * cannot start with `pilgrim_`.
     *
     * @param  array<int>|null  $tags  Replaces all the tags of the contact.
     * @param  array<string, mixed>  $customFields
     */
    public function upsert(
        string $email,
        string $name,
        ?float $mrr = null,
        ?float $ltv = null,
        ?string $avatar = null,
        ?array $tags = null,
        array $customFields = [],
    ): Contact {
        // An upsert by email has the same result when the SDK sends it again.
        $response = $this->transport->post('contacts', self::filled([
            'email' => $email,
            'name' => $name,
            'mrr' => $mrr,
            'ltv' => $ltv,
            'avatar' => $avatar,
            'tags' => self::ids($tags),
            'metadata' => $customFields === [] ? null : $customFields,
        ]), idempotent: true);

        return Contact::fromArray($response->json());
    }

    /**
     * Change the fields that you give. The email must be unique in the client.
     *
     * This operation does not change the custom fields. To change them, call
     * `upsert()` with the email of the contact.
     *
     * @param  array<int>|null  $tags  Replaces all the tags of the contact.
     */
    public function update(
        int $id,
        ?string $name = null,
        ?string $email = null,
        ?float $mrr = null,
        ?float $ltv = null,
        ?string $avatar = null,
        ?array $tags = null,
    ): Contact {
        $response = $this->transport->patch("contacts/{$id}", self::filled([
            'name' => $name,
            'email' => $email,
            'mrr' => $mrr,
            'ltv' => $ltv,
            'avatar' => $avatar,
            'tags' => self::ids($tags),
        ]));

        return Contact::fromArray($response->json());
    }
}
