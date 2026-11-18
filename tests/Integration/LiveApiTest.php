<?php

declare(strict_types=1);

/*
 * These tests call a real Feedback Pilgrim instance. They run only when
 * FEEDBACKPILGRIM_TEST_URL and FEEDBACKPILGRIM_TEST_TOKEN are set.
 *
 * The tests are read-only: they list the posts and the tags.
 */

use FeedbackPilgrim\FeedbackPilgrim;

function liveClient(): FeedbackPilgrim
{
    return new FeedbackPilgrim(
        url: (string) getenv('FEEDBACKPILGRIM_TEST_URL'),
        token: (string) getenv('FEEDBACKPILGRIM_TEST_TOKEN'),
        retries: 1,
    );
}

beforeEach(function () {
    if (! getenv('FEEDBACKPILGRIM_TEST_URL') || ! getenv('FEEDBACKPILGRIM_TEST_TOKEN')) {
        $this->markTestSkipped('Set FEEDBACKPILGRIM_TEST_URL and FEEDBACKPILGRIM_TEST_TOKEN to run the integration tests.');
    }
});

it('lists the posts', function () {
    $page = liveClient()->posts()->list(perPage: 5);

    expect($page->currentPage)->toBe(1)
        ->and($page->perPage)->toBe(5)
        ->and(count($page))->toBeLessThanOrEqual(5);
})->group('integration');

it('lists the tags', function () {
    $page = liveClient()->tags()->list();

    expect($page->currentPage)->toBe(1)
        ->and($page->total)->toBeGreaterThanOrEqual(count($page));
})->group('integration');
