<?php

declare(strict_types=1);

use FeedbackPilgrim\Data\Post;
use FeedbackPilgrim\Resources\Posts;
use FeedbackPilgrim\Resources\ReleaseNotes;
use FeedbackPilgrim\Resources\Testimonials;

it('lists posts with the filters', function () {
    $history = [];
    $page = pilgrim([json(paginator([postPayload()]))], $history)->posts()->list(
        search: 'dark mode',
        status: Post::PENDING,
        boardId: 1,
        tag: 'feature',
        published: false,
        sortBy: 'title',
        sortDirection: 'asc',
        perPage: 50,
        page: 2,
    );

    expect(sent($history[0]['request']))->toBe('GET /api/public/v1/posts?search=dark mode&status=pending&board_id=1&tag=feature&published=false&sort_by=title&sort_direction=asc&per_page=50&page=2')
        ->and($page->items)->toHaveCount(1)
        ->and($page->first())->toBeInstanceOf(Post::class);
});

it('reads a post with its nested contact, tags and votes', function () {
    $post = pilgrim([json(postPayload(['comments' => [['id' => 5, 'content' => 'Yes please.']]]))])->posts()->find(1);

    expect($post->id)->toBe(1)
        ->and($post->title)->toBe('Add dark mode')
        ->and($post->status)->toBe('in_progress')
        ->and($post->boardId)->toBe(1)
        ->and($post->stageId)->toBe(4)
        ->and($post->isPublished())->toBeFalse()
        ->and($post->createdAt?->format('Y-m-d'))->toBe('2026-06-01')
        ->and($post->contact?->email)->toBe('jane@example.com')
        ->and($post->contact?->mrr)->toBe(149.99)
        ->and($post->contact?->customFields)->toBe(['plan' => 'pro'])
        ->and($post->tags[0]->title)->toBe('feature')
        ->and($post->votes)->toHaveCount(1)
        ->and($post->comments[0]['content'])->toBe('Yes please.')
        ->and($post->attributes['client_id'])->toBe(1);
});

it('creates a post and reads its stage', function () {
    $history = [];
    $post = pilgrim([json(postPayload([
        'status' => 'pending',
        'contact' => null,
        'stage' => ['id' => 1, 'client_id' => 1, 'name' => 'Open', 'slug' => 'open', 'color' => '#94a3b8', 'type' => 'open', 'show_on_roadmap' => false, 'position' => 1],
    ]), 201)], $history)->posts()->create('Add dark mode', 'Please add a dark theme.', status: 'pending', boardId: 1, tags: [1, 2]);

    expect(sent($history[0]['request']))->toBe('POST /api/public/v1/posts')
        ->and(body($history[0]['request']))->toBe([
            'title' => 'Add dark mode',
            'content' => 'Please add a dark theme.',
            'status' => 'pending',
            'board_id' => 1,
            'tags' => [1, 2],
        ])
        ->and($post->contact)->toBeNull()
        ->and($post->stage?->name)->toBe('Open')
        ->and($post->stage?->isComplete())->toBeFalse();
});

it('sends only the given fields on an update, with PATCH', function () {
    $history = [];
    pilgrim([json(postPayload(['status' => 'completed']))], $history)->posts()->update(1, status: Post::COMPLETED, tags: []);

    expect(sent($history[0]['request']))->toBe('PATCH /api/public/v1/posts/1')
        ->and(body($history[0]['request']))->toBe(['status' => 'completed', 'tags' => []]);
});

it('has no delete method, because the API always answers 405', function () {
    expect(method_exists(Posts::class, 'delete'))->toBeFalse()
        ->and(method_exists(ReleaseNotes::class, 'delete'))->toBeFalse()
        ->and(method_exists(Testimonials::class, 'delete'))->toBeFalse();
});
