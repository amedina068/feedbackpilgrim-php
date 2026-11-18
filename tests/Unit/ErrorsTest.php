<?php

declare(strict_types=1);

use FeedbackPilgrim\Exceptions\ApiException;
use FeedbackPilgrim\Exceptions\AuthenticationException;
use FeedbackPilgrim\Exceptions\AuthorizationException;
use FeedbackPilgrim\Exceptions\ConnectionException;
use FeedbackPilgrim\Exceptions\FeedbackPilgrimException;
use FeedbackPilgrim\Exceptions\MethodNotAllowedException;
use FeedbackPilgrim\Exceptions\NotFoundException;
use FeedbackPilgrim\Exceptions\RateLimitException;
use FeedbackPilgrim\Exceptions\ServerException;
use FeedbackPilgrim\Exceptions\ValidationException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

it('throws a typed exception for each status', function (int $status, string $class, array $body, string $message) {
    try {
        pilgrim([json($body, $status)])->posts()->find(1);
    } catch (ApiException $exception) {
        expect($exception)->toBeInstanceOf($class)
            ->toBeInstanceOf(FeedbackPilgrimException::class)
            ->and($exception->getMessage())->toBe($message)
            ->and($exception->status)->toBe($status)
            ->and($exception->getCode())->toBe($status)
            ->and($exception->body())->toBe($body);

        return;
    }

    $this->fail('No exception was thrown.');
})->with([
    'authentication with message' => [401, AuthenticationException::class, ['message' => 'Unauthenticated.'], 'Unauthenticated.'],
    'authentication with error' => [401, AuthenticationException::class, ['error' => 'Invalid API token'], 'Invalid API token'],
    'authorization' => [403, AuthorizationException::class, ['message' => 'This action is unauthorized.'], 'This action is unauthorized.'],
    'not found' => [404, NotFoundException::class, ['message' => 'No query results for model [App\\Models\\Post] 1'], 'No query results for model [App\\Models\\Post] 1'],
    'method not allowed' => [405, MethodNotAllowedException::class, ['message' => 'Method not allowed'], 'Method not allowed'],
    'validation' => [422, ValidationException::class, ['message' => 'The title field is required.'], 'The title field is required.'],
    'rate limit' => [429, RateLimitException::class, ['message' => 'Too Many Attempts.'], 'Too Many Attempts.'],
    'server' => [500, ServerException::class, ['message' => 'Server Error'], 'Server Error'],
    'bad gateway' => [502, ServerException::class, ['message' => 'Bad Gateway'], 'Bad Gateway'],
    'other client error' => [418, ApiException::class, ['message' => 'I am a teapot'], 'I am a teapot'],
]);

it('gives the field errors of a validation error', function () {
    try {
        pilgrim([json([
            'message' => 'The title field is required. (and 1 more error)',
            'errors' => ['title' => ['The title field is required.'], 'tags.0' => ['The selected tags.0 is invalid.']],
        ], 422)])->posts()->create('', '');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toBe([
            'title' => ['The title field is required.'],
            'tags.0' => ['The selected tags.0 is invalid.'],
        ])->and($exception->first('title'))->toBe('The title field is required.')
            ->and($exception->first('content'))->toBeNull();

        return;
    }

    $this->fail('No validation exception was thrown.');
});

it('gives the Retry-After value and the limit headers of a rate limit error', function () {
    try {
        pilgrim([json(['message' => 'Too Many Attempts.'], 429, ['Retry-After' => '42', 'X-RateLimit-Limit' => '120', 'X-RateLimit-Remaining' => '0'])])->tags()->list();
    } catch (RateLimitException $exception) {
        expect($exception->retryAfter())->toBe(42)
            ->and($exception->limit())->toBe(120)
            ->and($exception->remaining())->toBe(0);

        return;
    }

    $this->fail('No rate limit exception was thrown.');
});

it('maps the 405 of an unsupported operation', function () {
    pilgrim([json(['message' => 'Method not allowed'], 405)])->tags()->delete(1);
})->throws(MethodNotAllowedException::class, 'Method not allowed');

it('uses a default message when the body is not JSON', function () {
    try {
        pilgrim([new Response(503, [], '<html>down</html>')])->tags()->list();
    } catch (ServerException $exception) {
        expect($exception->getMessage())->toBe('The Feedback Pilgrim instance answered with HTTP status 503.')
            ->and($exception->body())->toBe([]);

        return;
    }

    $this->fail('No server exception was thrown.');
});

it('throws a connection exception when the instance cannot be reached', function () {
    pilgrim([new ConnectException('cURL error 7: Failed to connect to feedback.test port 443', new Request('GET', 'https://feedback.test'))])
        ->tags()->list();
})->throws(ConnectionException::class, 'Failed to connect');

it('knows when a connection error happened before the request was sent', function (string $message, bool $sent) {
    $exception = ConnectionException::from(new ConnectException($message, new Request('GET', 'https://feedback.test')));

    expect($exception->requestSent)->toBe($sent);
})->with([
    'refused' => ['cURL error 7: Failed to connect to feedback.test port 443: Connection refused', false],
    'dns' => ['cURL error 6: Could not resolve host: feedback.test', false],
    'connect timeout' => ['cURL error 28: Connection timed out after 10001 milliseconds', false],
    'read timeout' => ['cURL error 28: Operation timed out after 30001 milliseconds with 0 bytes received', true],
    'reset' => ['cURL error 56: Recv failure: Connection reset by peer', true],
]);
