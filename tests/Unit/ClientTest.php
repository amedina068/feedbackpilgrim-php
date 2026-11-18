<?php

declare(strict_types=1);

use FeedbackPilgrim\Exceptions\MissingCredentialsException;
use FeedbackPilgrim\Exceptions\ServerException;
use FeedbackPilgrim\Exceptions\UnexpectedResponseException;
use FeedbackPilgrim\FeedbackPilgrim;
use GuzzleHttp\Psr7\Response;

it('sends the token, the JSON headers and the user agent', function () {
    $history = [];
    pilgrim([json(paginator([]))], $history)->tags()->list();

    $request = $history[0]['request'];

    expect((string) $request->getUri())->toBe('https://feedback.test/api/public/v1/tags')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer 1|test-token')
        ->and($request->getHeaderLine('Accept'))->toBe('application/json')
        ->and($request->getHeaderLine('User-Agent'))->toBe('feedbackpilgrim-php/'.FeedbackPilgrim::VERSION);
});

it('sends a JSON body with a content type', function () {
    $history = [];
    pilgrim([json(tagPayload(), 201)], $history)->tags()->create('feature');

    expect($history[0]['request']->getHeaderLine('Content-Type'))->toBe('application/json')
        ->and(body($history[0]['request']))->toBe(['title' => 'feature']);
});

it('removes a trailing slash from the instance URL', function () {
    expect((new FeedbackPilgrim(' https://feedback.test/ ', 'token'))->url)->toBe('https://feedback.test');
});

it('needs an instance URL', function () {
    new FeedbackPilgrim('', 'token');
})->throws(MissingCredentialsException::class, 'FEEDBACKPILGRIM_URL');

it('needs a token before the first request', function () {
    (new FeedbackPilgrim('https://feedback.test'))->tags()->list();
})->throws(MissingCredentialsException::class, 'FEEDBACKPILGRIM_TOKEN');

it('keeps the token and the identity secret out of debug output', function () {
    $client = new FeedbackPilgrim('https://feedback.test', 'secret-token', identitySecret: 'secret-identity');

    expect(print_r($client, true))->not->toContain('secret-token')->not->toContain('secret-identity')
        ->and(var_export((fn () => $this->transport->__debugInfo())->call($client), true))->not->toContain('secret-token');
});

it('throws a clear error when the body is not JSON', function () {
    pilgrim([new Response(200, ['Content-Type' => 'text/html'], '<html>Login</html>')])->posts()->find(1);
})->throws(UnexpectedResponseException::class, 'not JSON');

it('returns a sign-in link for a contact', function () {
    $history = [];
    $url = pilgrim([json(['auth_url' => 'https://acme.test/authenticate/abc?signature=x'])], $history)
        ->signInUrl('jane@example.com', name: 'Jane Doe', mrr: 99.99, customFields: ['plan' => 'premium']);

    expect($url)->toBe('https://acme.test/authenticate/abc?signature=x')
        ->and(sent($history[0]['request']))->toBe('POST /api/public/v1/init')
        ->and(body($history[0]['request']))->toBe(['contact' => [
            'email' => 'jane@example.com',
            'name' => 'Jane Doe',
            'mrr' => 99.99,
            'plan' => 'premium',
        ]]);
});

it('returns null when the instance answers init with an empty array', function () {
    expect(pilgrim([json([])])->signInUrl(''))->toBeNull();
});

it('does not send init again after a server error', function () {
    $history = [];

    $client = pilgrim([json([], 500), json(['auth_url' => 'x'])], $history, noSleep(2));

    expect(fn () => $client->signInUrl('jane@example.com'))
        ->toThrow(ServerException::class)
        ->and($history)->toHaveCount(1);
});
