<?php

declare(strict_types=1);

namespace FeedbackPilgrim\Resources;

use FeedbackPilgrim\Data\Page;
use FeedbackPilgrim\Data\Post;
use Generator;

/**
 * The posts of the client: feature requests and other feedback.
 *
 * The API cannot delete a post.
 */
final readonly class Posts extends Resource
{
    /**
     * One page of posts, with their contact, tags and votes.
     *
     * @param  string|null  $status  `pending`, `in_progress` or `completed`.
     * @param  string|null  $tag  The exact title of a tag.
     * @param  bool|null  $published  True gives the published posts. False gives the drafts.
     * @param  string|null  $sortBy  `created_at` (default), `updated_at`, `title`, `status` or `published_at`.
     * @param  string|null  $sortDirection  `asc` or `desc` (default).
     * @param  int|null  $perPage  1 to 100. The default is 15.
     * @return Page<Post>
     */
    public function list(
        ?string $search = null,
        ?string $status = null,
        ?int $boardId = null,
        ?string $tag = null,
        ?bool $published = null,
        ?string $sortBy = null,
        ?string $sortDirection = null,
        ?int $perPage = null,
        int $page = 1,
    ): Page {
        $body = $this->transport->get('posts', self::query([
            'search' => $search,
            'status' => $status,
            'board_id' => $boardId,
            'tag' => $tag,
            'published' => $published,
            'sort_by' => $sortBy,
            'sort_direction' => $sortDirection,
            'per_page' => $perPage,
            'page' => $page === 1 ? null : $page,
        ]))->json();

        return Page::fromBody($body, Post::fromArray(...));
    }

    /**
     * Every post that matches the filters. The SDK reads the next page when
     * you reach the end of the current page.
     *
     * @return Generator<int, Post>
     */
    public function all(
        ?string $search = null,
        ?string $status = null,
        ?int $boardId = null,
        ?string $tag = null,
        ?bool $published = null,
        ?string $sortBy = null,
        ?string $sortDirection = null,
        int $perPage = 100,
    ): Generator {
        return Page::walk(fn (int $page): Page => $this->list(
            $search, $status, $boardId, $tag, $published, $sortBy, $sortDirection, $perPage, $page,
        ));
    }

    /**
     * One post, with its contact, tags, votes and comments.
     */
    public function find(int $id): Post
    {
        return Post::fromArray($this->transport->get("posts/{$id}")->json());
    }

    /**
     * Create a post. Without a board, the post goes on the first board of
     * the client.
     *
     * @param  array<int>|null  $tags  The IDs of tags of the client.
     */
    public function create(
        string $title,
        string $content,
        ?string $status = null,
        ?int $boardId = null,
        ?array $tags = null,
    ): Post {
        $response = $this->transport->post('posts', self::filled([
            'title' => $title,
            'content' => $content,
            'status' => $status,
            'board_id' => $boardId,
            'tags' => self::ids($tags),
        ]));

        return Post::fromArray($response->json());
    }

    /**
     * Change the fields that you give. A new status moves the post to the
     * first stage of that type, and the instance sends the same emails and
     * webhooks as the dashboard.
     *
     * @param  array<int>|null  $tags  Replaces all the tags of the post.
     */
    public function update(
        int $id,
        ?string $title = null,
        ?string $content = null,
        ?string $status = null,
        ?int $boardId = null,
        ?array $tags = null,
    ): Post {
        $response = $this->transport->patch("posts/{$id}", self::filled([
            'title' => $title,
            'content' => $content,
            'status' => $status,
            'board_id' => $boardId,
            'tags' => self::ids($tags),
        ]));

        return Post::fromArray($response->json());
    }
}
