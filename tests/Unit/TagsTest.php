<?php

declare(strict_types=1);
use GuzzleHttp\Psr7\Response;

it('lists tags with their counts', function () {
    $history = [];
    $page = pilgrim([json(paginator([tagPayload(['posts_count' => 4, 'contacts_count' => 2])]))], $history)->tags()->list(search: 'feat', sortBy: 'created_at', sortDirection: 'desc');

    expect(sent($history[0]['request']))->toBe('GET /api/public/v1/tags?search=feat&sort_by=created_at&sort_direction=desc')
        ->and($page->first()?->postsCount)->toBe(4)
        ->and($page->first()?->contactsCount)->toBe(2);
});

it('creates a tag', function () {
    $history = [];
    $tag = pilgrim([json(tagPayload(), 201)], $history)->tags()->create('feature');

    expect(sent($history[0]['request']))->toBe('POST /api/public/v1/tags')
        ->and($tag->title)->toBe('feature')
        ->and($tag->postsCount)->toBeNull();
});

it('renames a tag with PATCH', function () {
    $history = [];
    $tag = pilgrim([json(tagPayload(['title' => 'enhancement']))], $history)->tags()->update(1, 'enhancement');

    expect(sent($history[0]['request']))->toBe('PATCH /api/public/v1/tags/1')
        ->and(body($history[0]['request']))->toBe(['title' => 'enhancement'])
        ->and($tag->title)->toBe('enhancement');
});

it('deletes a tag and accepts the empty 204 response', function () {
    $history = [];
    pilgrim([new Response(204)], $history)->tags()->delete(1);

    expect(sent($history[0]['request']))->toBe('DELETE /api/public/v1/tags/1');
});
