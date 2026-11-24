<?php

declare(strict_types=1);

use FeedbackPilgrim\Exceptions\ConnectionException;
use FeedbackPilgrim\Exceptions\RateLimitException;
use FeedbackPilgrim\Exceptions\ServerException;
use FeedbackPilgrim\Http\Response;
use FeedbackPilgrim\Http\RetryPolicy;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;

function connectionRefused(): ConnectException
{
    return new ConnectException('cURL error 7: Failed to connect: Connection refused', new Request('GET', 'https://feedback.test'));
}

function readTimeout(): ConnectException
{
    return new ConnectException('cURL error 28: Operation timed out after 30001 milliseconds with 0 bytes received', new Request('GET', 'https://feedback.test'));
}

it('retries a GET after a server error', function () {
    $history = [];
    $delays = [];
    $client = pilgrim([json([], 500), json([], 503), json(postPayload())], $history, noSleep(2, $delays));

    expect($client->posts()->find(1)->id)->toBe(1)
        ->and($history)->toHaveCount(3)
        ->and($delays)->toHaveCount(2);
});

it('throws the last error when the retries are used up', function () {
    $history = [];

    $client = pilgrim([json([], 500), json([], 500), json([], 500)], $history, noSleep(2));

    expect(fn () => $client->posts()->find(1))
        ->toThrow(ServerException::class)
        ->and($history)->toHaveCount(3);
});

it('does not retry when retries are off', function () {
    $history = [];

    $client = pilgrim([json([], 500), json(postPayload())], $history, 0);

    expect(fn () => $client->posts()->find(1))
        ->toThrow(ServerException::class)
        ->and($history)->toHaveCount(1);
});

it('does not retry a create after a server error', function () {
    $history = [];

    $client = pilgrim([json([], 500), json(postPayload(), 201)], $history, noSleep(2));

    expect(fn () => $client->posts()->create('A', 'B'))
        ->toThrow(ServerException::class)
        ->and($history)->toHaveCount(1);
});

it('retries an update and a delete after a server error', function () {
    $history = [];
    $client = pilgrim([json([], 502), json(tagPayload()), json([], 502), new GuzzleHttp\Psr7\Response(204)], $history, noSleep(1));

    $client->tags()->update(1, 'feature');
    $client->tags()->delete(1);

    expect($history)->toHaveCount(4);
});

it('retries a create after a rate limit and waits for Retry-After', function () {
    $history = [];
    $delays = [];
    $client = pilgrim([json(['message' => 'Too Many Attempts.'], 429, ['Retry-After' => '3']), json(postPayload(), 201)], $history, noSleep(2, $delays));

    expect($client->posts()->create('A', 'B')->id)->toBe(1)
        ->and($history)->toHaveCount(2)
        ->and($delays)->toBe([3000]);
});

it('does not wait when Retry-After is longer than the limit', function () {
    $history = [];
    $delays = [];

    $client = pilgrim([json([], 429, ['Retry-After' => '120']), json(paginator([]))], $history, noSleep(2, $delays, maxRetryAfter: 60));

    expect(fn () => $client->tags()->list())
        ->toThrow(RateLimitException::class)
        ->and($history)->toHaveCount(1)
        ->and($delays)->toBe([]);
});

it('retries a create after a connection error before the send', function (string $message) {
    $history = [];
    $error = new ConnectException($message, new Request('POST', 'https://feedback.test'));

    expect(pilgrim([$error, json(postPayload(), 201)], $history, noSleep(2))->posts()->create('A', 'B')->id)->toBe(1)
        ->and($history)->toHaveCount(2);
})->with([
    'dns' => ['cURL error 6: Could not resolve host: feedback.test'],
    'refused' => ['cURL error 7: Failed to connect to feedback.test port 443: Connection refused'],
    'connect timeout' => ['cURL error 28: Connection timed out after 10001 milliseconds'],
]);

it('retries signInUrl() after a refused connection', function () {
    $history = [];

    expect(pilgrim([connectionRefused(), json(['auth_url' => 'https://a.test/x'])], $history, noSleep(2))->signInUrl('jane@example.com'))->toBe('https://a.test/x')
        ->and($history)->toHaveCount(2);
});

it('does not retry a create after a reset after the send', function () {
    $history = [];
    $reset = new ConnectException('cURL error 56: Recv failure: Connection reset by peer', new Request('POST', 'https://feedback.test'));

    $client = pilgrim([$reset, json(postPayload(), 201)], $history, noSleep(2));

    expect(fn () => $client->posts()->create('A', 'B'))
        ->toThrow(ConnectionException::class)
        ->and($history)->toHaveCount(1);
});

it('retries a GET after a refused connection', function () {
    $history = [];

    expect(pilgrim([connectionRefused(), json(postPayload())], $history, noSleep(2))->posts()->find(1)->id)->toBe(1)
        ->and($history)->toHaveCount(2);
});

it('does not retry a create after a timeout, because the instance can have received it', function () {
    $history = [];

    $client = pilgrim([readTimeout(), json(postPayload(), 201)], $history, noSleep(2));

    expect(fn () => $client->posts()->create('A', 'B'))
        ->toThrow(ConnectionException::class)
        ->and($history)->toHaveCount(1);
});

it('retries a GET after a timeout', function () {
    $history = [];

    expect(pilgrim([readTimeout(), json(postPayload())], $history, noSleep(2))->posts()->find(1)->id)->toBe(1)
        ->and($history)->toHaveCount(2);
});

it('grows the delay exponentially with jitter', function () {
    $policy = new RetryPolicy(retries: 5, baseDelayMs: 100, maxDelayMs: 1000);

    foreach ([1 => 100, 2 => 200, 3 => 400, 4 => 800, 5 => 1000] as $attempt => $ceiling) {
        for ($i = 0; $i < 20; $i++) {
            expect($policy->delay($attempt))->toBeGreaterThanOrEqual(intdiv($ceiling, 2))->toBeLessThanOrEqual($ceiling);
        }
    }
});

it('uses Retry-After in place of the computed delay', function () {
    expect((new RetryPolicy)->delay(1, 7))->toBe(7000);
});

it('reads Retry-After as seconds or as an HTTP date', function () {
    $date = gmdate('D, d M Y H:i:s \G\M\T', time() + 30);

    expect((new Response(429, '', ['retry-after' => ['5']]))->retryAfter())->toBe(5)
        ->and((new Response(429, '', ['retry-after' => [$date]]))->retryAfter())->toBeGreaterThanOrEqual(28)->toBeLessThanOrEqual(30)
        ->and((new Response(429, '', ['retry-after' => ['soon']]))->retryAfter())->toBeNull()
        ->and((new Response(429, ''))->retryAfter())->toBeNull();
});

it('sleeps for real when no sleep callback is given', function () {
    $start = microtime(true);

    (new RetryPolicy)->sleep(20);

    expect(microtime(true) - $start)->toBeGreaterThanOrEqual(0.015);
});

it('uses two retries by default', function () {
    expect((new RetryPolicy)->retries)->toBe(2)
        ->and(RetryPolicy::none()->retries)->toBe(0);
});
