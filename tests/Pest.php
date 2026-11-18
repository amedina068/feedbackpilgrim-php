<?php

declare(strict_types=1);

use FeedbackPilgrim\FeedbackPilgrim;
use FeedbackPilgrim\Http\RetryPolicy;
use FeedbackPilgrim\Tests\Laravel\TestCase;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Psr\Http\Message\RequestInterface;

uses(TestCase::class)->in('Laravel');

/**
 * A client that answers from a queue of responses and records each request.
 *
 * @param  list<PsrResponse|Throwable>  $queue
 * @param  array<int, array{request: RequestInterface}>  $history
 */
function pilgrim(array $queue = [], array &$history = [], int|RetryPolicy $retries = 0, ?string $identitySecret = null): FeedbackPilgrim
{
    $stack = HandlerStack::create(new MockHandler($queue));
    $stack->push(Middleware::history($history));

    return new FeedbackPilgrim(
        url: 'https://feedback.test',
        token: '1|test-token',
        identitySecret: $identitySecret,
        retries: $retries,
        httpClient: new GuzzleClient(['handler' => $stack]),
    );
}

/**
 * @param  array<array-key, mixed>  $body
 * @param  array<string, string>  $headers
 */
function json(array $body, int $status = 200, array $headers = []): PsrResponse
{
    return new PsrResponse($status, ['Content-Type' => 'application/json'] + $headers, json_encode($body, JSON_THROW_ON_ERROR));
}

/**
 * The method, the path and the decoded query of a recorded request, for
 * example "GET /api/public/v1/posts?status=pending".
 */
function sent(RequestInterface $request): string
{
    $uri = $request->getUri();
    $query = rawurldecode($uri->getQuery());

    return $request->getMethod().' '.$uri->getPath().($query === '' ? '' : '?'.$query);
}

/**
 * The decoded JSON body of a recorded request.
 *
 * @return array<array-key, mixed>
 */
function body(RequestInterface $request): array
{
    return json_decode((string) $request->getBody(), true, flags: JSON_THROW_ON_ERROR);
}

/**
 * A retry policy that records each delay and does not sleep.
 *
 * @param  list<int>  $delays
 */
function noSleep(int $retries, array &$delays = [], int $maxRetryAfter = 60): RetryPolicy
{
    return new RetryPolicy(
        retries: $retries,
        maxRetryAfter: $maxRetryAfter,
        sleep: function (int $milliseconds) use (&$delays): void {
            $delays[] = $milliseconds;
        },
    );
}

/**
 * A flat Laravel paginator body.
 *
 * @param  list<array<string, mixed>>  $data
 * @return array<string, mixed>
 */
function paginator(array $data, int $page = 1, int $lastPage = 1, int $perPage = 15, ?int $total = null): array
{
    return [
        'current_page' => $page,
        'data' => $data,
        'first_page_url' => 'https://feedback.test/api/public/v1/posts?page=1',
        'from' => $data === [] ? null : 1,
        'last_page' => $lastPage,
        'last_page_url' => "https://feedback.test/api/public/v1/posts?page={$lastPage}",
        'links' => [],
        'next_page_url' => $page < $lastPage ? 'https://feedback.test/api/public/v1/posts?page='.($page + 1) : null,
        'path' => 'https://feedback.test/api/public/v1/posts',
        'per_page' => $perPage,
        'prev_page_url' => null,
        'to' => $data === [] ? null : count($data),
        'total' => $total ?? count($data),
    ];
}

/**
 * @return array<string, mixed>
 */
function tagPayload(array $overrides = []): array
{
    return $overrides + ['id' => 1, 'client_id' => 1, 'title' => 'feature', 'created_at' => '2026-06-01T09:00:00.000000Z', 'updated_at' => '2026-06-01T09:00:00.000000Z'];
}

/**
 * A contact inside another record: the custom fields are one JSON string.
 *
 * @return array<string, mixed>
 */
function nestedContactPayload(array $overrides = []): array
{
    return $overrides + [
        'id' => 7,
        'uuid' => '6e48ad77-fb51-449f-99d2-9c5cc2d9d0c5',
        'user_id' => null,
        'client_id' => 1,
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'metadata' => '{"plan":"pro","pilgrim_last_login":"2026-06-01 09:00:00"}',
        'notes' => null,
        'avatar' => 'https://feedback.test/storage/avatars/7/avatar.png',
        'mrr' => '149.99',
        'ltv' => 1800,
        'top_customer' => 0,
        'created_at' => '2026-06-01T09:00:00.000000Z',
        'updated_at' => '2026-06-01T09:00:00.000000Z',
    ];
}

/**
 * A contact from the Contacts operations: each custom field is a field.
 *
 * @return array<string, mixed>
 */
function contactPayload(array $overrides = []): array
{
    $contact = nestedContactPayload();
    unset($contact['metadata']);

    return $overrides + $contact + ['plan' => 'pro', 'seats' => 5, 'tags' => [tagPayload()], 'votes' => []];
}

/**
 * @return array<string, mixed>
 */
function postPayload(array $overrides = []): array
{
    return $overrides + [
        'id' => 1,
        'client_id' => 1,
        'stage_id' => 4,
        'board_id' => 1,
        'contact_id' => 7,
        'title' => 'Add dark mode',
        'content' => 'Please add a dark theme.',
        'notes' => null,
        'status' => 'in_progress',
        'stage_position' => null,
        'published_at' => null,
        'archived_at' => null,
        'created_at' => '2026-06-01T09:00:00.000000Z',
        'updated_at' => '2026-06-01T09:00:00.000000Z',
        'contact' => nestedContactPayload(),
        'tags' => [tagPayload()],
        'votes' => [['id' => 3, 'contact_id' => 7, 'votable_type' => 'App\\Models\\Post', 'votable_id' => 1]],
    ];
}

/**
 * @return array<string, mixed>
 */
function releaseNotePayload(array $overrides = []): array
{
    return $overrides + [
        'id' => 1,
        'client_id' => 1,
        'user_id' => 1,
        'title' => 'Version 2.1.0',
        'content' => "## New\n- Dark mode",
        'slug' => 'version-210-20260601',
        'published_at' => null,
        'created_at' => '2026-06-01T09:00:00.000000Z',
        'updated_at' => '2026-06-01T09:00:00.000000Z',
        'tags' => [tagPayload()],
        'votes' => [],
        'comments' => [],
    ];
}

/**
 * @return array<string, mixed>
 */
function testimonialPayload(array $overrides = []): array
{
    return $overrides + [
        'id' => 1,
        'client_id' => 1,
        'contact_id' => 7,
        'title' => null,
        'description' => 'The roadmap keeps our customers in the loop.',
        'rating' => 5,
        'avatar_image' => null,
        'username' => 'jane_doe',
        'subtitle' => null,
        'approved_at' => null,
        'created_at' => '2026-06-01T09:00:00.000000Z',
        'updated_at' => '2026-06-01T09:00:00.000000Z',
        'contact' => nestedContactPayload(),
        'votes' => [],
        'comments' => [],
    ];
}

/**
 * @return array<string, mixed>
 */
function vectors(string $name): array
{
    return json_decode((string) file_get_contents(__DIR__."/Fixtures/{$name}.json"), true, flags: JSON_THROW_ON_ERROR);
}
