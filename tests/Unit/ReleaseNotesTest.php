<?php

declare(strict_types=1);

use FeedbackPilgrim\Data\ReleaseNote;

it('lists release notes with the filters', function () {
    $history = [];
    $page = pilgrim([json(paginator([releaseNotePayload()]))], $history)->releaseNotes()->list(published: true, tag: 'feature', sortBy: 'published_at');

    expect(sent($history[0]['request']))->toBe('GET /api/public/v1/release-notes?published=true&tag=feature&sort_by=published_at')
        ->and($page->first())->toBeInstanceOf(ReleaseNote::class);
});

it('reads one release note', function () {
    $history = [];
    $note = pilgrim([json(releaseNotePayload(['published_at' => '2026-06-02T10:00:00.000000Z']))], $history)->releaseNotes()->find(1);

    expect(sent($history[0]['request']))->toBe('GET /api/public/v1/release-notes/1')
        ->and($note->title)->toBe('Version 2.1.0')
        ->and($note->content)->toBe("## New\n- Dark mode")
        ->and($note->slug)->toBe('version-210-20260601')
        ->and($note->userId)->toBe(1)
        ->and($note->isPublished())->toBeTrue()
        ->and($note->tags[0]->id)->toBe(1);
});

it('creates a draft by default', function () {
    $history = [];
    pilgrim([json(releaseNotePayload(), 201)], $history)->releaseNotes()->create('Version 2.1.0', '## New', tags: [1]);

    expect(sent($history[0]['request']))->toBe('POST /api/public/v1/release-notes')
        ->and(body($history[0]['request']))->toBe(['title' => 'Version 2.1.0', 'content' => '## New', 'published' => false, 'tags' => [1]]);
});

it('publishes a release note with an update', function () {
    $history = [];
    pilgrim([json(releaseNotePayload())], $history)->releaseNotes()->update(1, title: 'Version 2.1.1', published: true);

    expect(sent($history[0]['request']))->toBe('PATCH /api/public/v1/release-notes/1')
        ->and(body($history[0]['request']))->toBe(['title' => 'Version 2.1.1', 'published' => true]);
});
