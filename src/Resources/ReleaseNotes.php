<?php

declare(strict_types=1);

namespace FeedbackPilgrim\Resources;

use FeedbackPilgrim\Data\Page;
use FeedbackPilgrim\Data\ReleaseNote;
use Generator;

/**
 * The changelog entries of the client.
 *
 * The API cannot delete a release note.
 */
final readonly class ReleaseNotes extends Resource
{
    /**
     * One page of release notes, with their tags, votes and comments.
     *
     * @param  bool|null  $published  True gives the published notes. False gives the drafts.
     * @param  string|null  $tag  The exact title of a tag.
     * @param  string|null  $sortBy  `created_at` (default), `updated_at`, `title` or `published_at`.
     * @param  string|null  $sortDirection  `asc` or `desc` (default).
     * @param  int|null  $perPage  1 to 100. The default is 15.
     * @return Page<ReleaseNote>
     */
    public function list(
        ?string $search = null,
        ?bool $published = null,
        ?string $tag = null,
        ?string $sortBy = null,
        ?string $sortDirection = null,
        ?int $perPage = null,
        int $page = 1,
    ): Page {
        $body = $this->transport->get('release-notes', self::query([
            'search' => $search,
            'published' => $published,
            'tag' => $tag,
            'sort_by' => $sortBy,
            'sort_direction' => $sortDirection,
            'per_page' => $perPage,
            'page' => $page === 1 ? null : $page,
        ]))->json();

        return Page::fromBody($body, ReleaseNote::fromArray(...));
    }

    /**
     * Every release note that matches the filters, page by page.
     *
     * @return Generator<int, ReleaseNote>
     */
    public function all(
        ?string $search = null,
        ?bool $published = null,
        ?string $tag = null,
        ?string $sortBy = null,
        ?string $sortDirection = null,
        int $perPage = 100,
    ): Generator {
        return Page::walk(fn (int $page): Page => $this->list(
            $search, $published, $tag, $sortBy, $sortDirection, $perPage, $page,
        ));
    }

    /**
     * One release note, with its tags, votes and comments.
     */
    public function find(int $id): ReleaseNote
    {
        return ReleaseNote::fromArray($this->transport->get("release-notes/{$id}")->json());
    }

    /**
     * Create a release note. The token user becomes the author, and the
     * instance makes the slug from the title.
     *
     * @param  string  $content  Markdown.
     * @param  bool  $published  False makes a draft.
     * @param  array<int>|null  $tags  The IDs of tags of the client.
     */
    public function create(string $title, string $content, bool $published = false, ?array $tags = null): ReleaseNote
    {
        $response = $this->transport->post('release-notes', self::filled([
            'title' => $title,
            'content' => $content,
            'published' => $published,
            'tags' => self::ids($tags),
        ]));

        return ReleaseNote::fromArray($response->json());
    }

    /**
     * Change the fields that you give.
     *
     * @param  bool|null  $published  True keeps a publication date that exists, or sets the current time. False clears it.
     * @param  array<int>|null  $tags  Replaces all the tags of the note.
     */
    public function update(
        int $id,
        ?string $title = null,
        ?string $content = null,
        ?bool $published = null,
        ?array $tags = null,
    ): ReleaseNote {
        $response = $this->transport->patch("release-notes/{$id}", self::filled([
            'title' => $title,
            'content' => $content,
            'published' => $published,
            'tags' => self::ids($tags),
        ]));

        return ReleaseNote::fromArray($response->json());
    }
}
