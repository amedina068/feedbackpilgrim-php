<?php

declare(strict_types=1);

it('lists the pending testimonials by default', function () {
    $history = [];
    $page = pilgrim([json(paginator([testimonialPayload()]))], $history)->testimonials()->list();

    expect(sent($history[0]['request']))->toBe('GET /api/public/v1/testimonials?approved=false')
        ->and($page->first()?->isApproved())->toBeFalse();
});

it('lists the approved testimonials with the filters', function () {
    $history = [];
    pilgrim([json(paginator([]))], $history)->testimonials()->list(approved: true, search: 'roadmap', rating: 5, sortBy: 'rating');

    expect(sent($history[0]['request']))->toBe('GET /api/public/v1/testimonials?approved=true&search=roadmap&rating=5&sort_by=rating');
});

it('reads one testimonial', function () {
    $testimonial = pilgrim([json(testimonialPayload(['approved_at' => '2026-06-15T10:00:00.000000Z']))])->testimonials()->find(1);

    expect($testimonial->description)->toBe('The roadmap keeps our customers in the loop.')
        ->and($testimonial->rating)->toBe(5)
        ->and($testimonial->username)->toBe('jane_doe')
        ->and($testimonial->contactId)->toBe(7)
        ->and($testimonial->contact?->customFields)->toBe(['plan' => 'pro'])
        ->and($testimonial->isApproved())->toBeTrue();
});

it('creates a testimonial', function () {
    $history = [];
    pilgrim([json(testimonialPayload(), 201)], $history)->testimonials()->create('Great.', 'jane_doe', rating: 5, contactId: 42);

    expect(sent($history[0]['request']))->toBe('POST /api/public/v1/testimonials')
        ->and(body($history[0]['request']))->toBe(['description' => 'Great.', 'username' => 'jane_doe', 'rating' => 5, 'contact_id' => 42]);
});

it('approves a testimonial with a date in UTC', function () {
    $history = [];
    pilgrim([json(testimonialPayload())], $history)->testimonials()->approve(1, new DateTimeImmutable('2026-06-15 12:00:00', new DateTimeZone('Europe/Madrid')));

    expect(sent($history[0]['request']))->toBe('PATCH /api/public/v1/testimonials/1')
        ->and(body($history[0]['request']))->toBe(['approved_at' => '2026-06-15T10:00:00Z']);
});

it('approves a testimonial now by default', function () {
    $history = [];
    pilgrim([json(testimonialPayload())], $history)->testimonials()->approve(1);

    $sentAt = new DateTimeImmutable(body($history[0]['request'])['approved_at']);

    expect(abs($sentAt->getTimestamp() - time()))->toBeLessThan(5);
});

it('updates a testimonial with a date string', function () {
    $history = [];
    pilgrim([json(testimonialPayload())], $history)->testimonials()->update(1, rating: 4, approvedAt: '2026-06-15T10:00:00Z');

    expect(body($history[0]['request']))->toBe(['rating' => 4, 'approved_at' => '2026-06-15T10:00:00Z']);
});
