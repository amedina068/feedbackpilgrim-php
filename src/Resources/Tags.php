<?php

declare(strict_types=1);

namespace FeedbackPilgrim\Resources;

use FeedbackPilgrim\Data\Page;
use FeedbackPilgrim\Data\Tag;
use Generator;

/**
 * The labels for posts, release notes and contacts.
 */
final readonly class Tags extends Resource
{
    /**
     * One page of tags, with `postsCount` and `contactsCount`.
     *
     * @param  string|null  $sortBy  `title` (default), `created_at` or `updated_at`.
     * @param  string|null  $sortDirection  `asc` (default) or `desc`.
     * @param  int|null  $perPage  1 to 100. The default is 15.
     * @return Page<Tag>
     */
    public function list(
        ?string $search = null,
        ?string $sortBy = null,
        ?string $sortDirection = null,
        ?int $perPage = null,
        int $page = 1,
    ): Page {
        $body = $this->transport->get('tags', self::query([
            'search' => $search,
            'sort_by' => $sortBy,
            'sort_direction' => $sortDirection,
            'per_page' => $perPage,
            'page' => $page === 1 ? null : $page,
        ]))->json();

        return Page::fromBody($body, Tag::fromArray(...));
    }

    /**
     * Every tag that matches the filters, page by page.
     *
     * @return Generator<int, Tag>
     */
    public function all(?string $search = null, ?string $sortBy = null, ?string $sortDirection = null, int $perPage = 100): Generator
    {
        return Page::walk(fn (int $page): Page => $this->list($search, $sortBy, $sortDirection, $perPage, $page));
    }

    /**
     * Create a tag. The title must be unique in the client.
     */
    public function create(string $title): Tag
    {
        return Tag::fromArray($this->transport->post('tags', ['title' => $title])->json());
    }

    /**
     * Change the title of a tag. The title must be unique in the client.
     */
    public function update(int $id, string $title): Tag
    {
        return Tag::fromArray($this->transport->patch("tags/{$id}", ['title' => $title])->json());
    }

    /**
     * Delete a tag. The instance first removes it from each post, release note
     * and contact.
     */
    public function delete(int $id): void
    {
        $this->transport->delete("tags/{$id}");
    }
}
