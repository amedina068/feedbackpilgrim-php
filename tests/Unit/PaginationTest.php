<?php

declare(strict_types=1);

use FeedbackPilgrim\Data\Page;

it('reads the paginator fields', function () {
    $page = Page::fromBody(paginator([['id' => 1], ['id' => 2]], page: 2, lastPage: 3, perPage: 2, total: 6), fn (array $item) => $item['id']);

    expect($page->items)->toBe([1, 2])
        ->and($page->currentPage)->toBe(2)
        ->and($page->lastPage)->toBe(3)
        ->and($page->perPage)->toBe(2)
        ->and($page->total)->toBe(6)
        ->and($page->nextPageUrl)->toBe('https://feedback.test/api/public/v1/posts?page=3')
        ->and($page->hasMore())->toBeTrue()
        ->and($page->nextPage())->toBe(3)
        ->and($page->isEmpty())->toBeFalse()
        ->and(count($page))->toBe(2)
        ->and(iterator_to_array($page))->toBe([1, 2]);
});

it('walks every page with the filters and the page size', function () {
    $history = [];
    $client = pilgrim([
        json(paginator([postPayload(['id' => 3]), postPayload(['id' => 2])], page: 1, lastPage: 2, perPage: 2)),
        json(paginator([postPayload(['id' => 1])], page: 2, lastPage: 2, perPage: 2)),
    ], $history);

    $ids = array_map(fn ($post) => $post->id, iterator_to_array($client->posts()->all(status: 'pending', perPage: 2), false));

    expect($ids)->toBe([3, 2, 1])
        ->and(sent($history[0]['request']))->toBe('GET /api/public/v1/posts?status=pending&per_page=2')
        ->and(sent($history[1]['request']))->toBe('GET /api/public/v1/posts?status=pending&per_page=2&page=2');
});

it('reads a page only when the caller reaches it', function () {
    $history = [];
    $client = pilgrim([json(paginator([tagPayload()], lastPage: 2)), json(paginator([tagPayload()], page: 2, lastPage: 2))], $history);

    $tags = $client->tags()->all();
    $tags->current();

    expect($history)->toHaveCount(1)
        ->and(sent($history[0]['request']))->toBe('GET /api/public/v1/tags?per_page=100');
});

it('stops after an empty first page', function () {
    $history = [];

    expect(iterator_to_array(pilgrim([json(paginator([]))], $history)->contacts()->all()))->toBe([])
        ->and($history)->toHaveCount(1);
});

it('stops on an empty page even when the last page is higher', function () {
    $history = [];
    $client = pilgrim([json(paginator([tagPayload()], lastPage: 3)), json(paginator([], page: 2, lastPage: 3))], $history);

    expect(iterator_to_array($client->tags()->all(), false))->toHaveCount(1)
        ->and($history)->toHaveCount(2);
});

it('walks each paginated resource', function (Closure $all, array $item) {
    $client = pilgrim([json(paginator([$item], lastPage: 2)), json(paginator([$item], page: 2, lastPage: 2))]);

    expect(iterator_to_array($all($client), false))->toHaveCount(2);
})->with([
    'posts' => [fn ($client) => $client->posts()->all(), postPayload()],
    'release notes' => [fn ($client) => $client->releaseNotes()->all(published: true), releaseNotePayload()],
    'contacts' => [fn ($client) => $client->contacts()->all(), contactPayload()],
    'testimonials' => [fn ($client) => $client->testimonials()->all(approved: true), testimonialPayload()],
    'tags' => [fn ($client) => $client->tags()->all(), tagPayload()],
]);
