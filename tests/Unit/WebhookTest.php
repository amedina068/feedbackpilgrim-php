<?php

declare(strict_types=1);

use FeedbackPilgrim\Exceptions\MissingCredentialsException;
use FeedbackPilgrim\Exceptions\WebhookVerificationException;
use FeedbackPilgrim\Webhooks\VerificationFailure;
use FeedbackPilgrim\Webhooks\Webhook;
use FeedbackPilgrim\Webhooks\WebhookEvent;

/**
 * @return array<string, array{0: array<string, mixed>}>
 */
function webhookVectors(): array
{
    $cases = vectors('webhook-signature-vectors')['cases'];

    return array_combine(array_column($cases, 'name'), array_map(fn (array $case) => [$case], $cases));
}

/**
 * @param  array<string, mixed>  $case
 * @return array<string, string>
 */
function vectorHeaders(array $case): array
{
    return [
        'Content-Type' => 'application/json',
        'X-FeedbackPilgrim-Event' => $case['event'],
        'X-FeedbackPilgrim-Delivery' => '9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d',
        'X-FeedbackPilgrim-Timestamp' => $case['timestamp'],
        'X-FeedbackPilgrim-Signature-V2' => $case['signature'],
    ];
}

function webhookSecret(): string
{
    return vectors('webhook-signature-vectors')['secret'];
}

it('matches each shared signature vector', function (array $case) {
    $now = (int) $case['timestamp'];

    expect(Webhook::isValid($case['body'], $case['signature'], $case['timestamp'], webhookSecret(), now: $now))->toBe($case['valid']);

    if (! $case['valid']) {
        expect(fn () => Webhook::verify($case['body'], vectorHeaders($case), webhookSecret(), now: $now))
            ->toThrow(WebhookVerificationException::class);

        return;
    }

    $event = Webhook::verify($case['body'], vectorHeaders($case), webhookSecret(), now: $now);

    expect($event->type)->toBe($case['event'])
        ->and($event->payload)->toBe(json_decode($case['body'], true))
        ->and($event->timestamp)->toBe($now);
})->with(fn () => webhookVectors());

it('gives the event, the data and the delivery ID', function () {
    $case = webhookVectors()['status change with a slash and accents in the text'][0];

    $event = Webhook::verify($case['body'], vectorHeaders($case), webhookSecret(), now: (int) $case['timestamp']);

    expect($event)->toBeInstanceOf(WebhookEvent::class)
        ->and($event->is(WebhookEvent::POST_STATUS_CHANGED))->toBeTrue()
        ->and($event->deliveryId)->toBe('9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d')
        ->and($event->data['title'])->toBe('Export to CSV/Excel')
        ->and($event->data['content'])->toBe('Café menus need it.')
        ->and($event->data['stage'])->toBe(['id' => 4, 'name' => 'In Progress', 'type' => 'active']);
});

it('reads the headers in any case and as lists, like a Symfony request', function () {
    $case = webhookVectors()['post created without a stage'][0];

    $event = Webhook::verify($case['body'], [
        'x-feedbackpilgrim-signature-v2' => [$case['signature']],
        'x-feedbackpilgrim-timestamp' => [$case['timestamp']],
        'x-feedbackpilgrim-delivery' => ['d-1'],
    ], webhookSecret(), now: (int) $case['timestamp']);

    expect($event->type)->toBe(WebhookEvent::POST_CREATED)
        ->and($event->deliveryId)->toBe('d-1')
        ->and($event->data['stage'])->toBeNull();
});

it('rejects a timestamp outside the default tolerance of 300 seconds', function () {
    $case = webhookVectors()['post completed'][0];
    $timestamp = (int) $case['timestamp'];

    expect(Webhook::isValid($case['body'], $case['signature'], $case['timestamp'], webhookSecret(), now: $timestamp + 300))->toBeTrue()
        ->and(Webhook::isValid($case['body'], $case['signature'], $case['timestamp'], webhookSecret(), now: $timestamp - 300))->toBeTrue()
        ->and(Webhook::isValid($case['body'], $case['signature'], $case['timestamp'], webhookSecret(), now: $timestamp + 301))->toBeFalse()
        ->and(Webhook::isValid($case['body'], $case['signature'], $case['timestamp'], webhookSecret(), now: $timestamp - 301))->toBeFalse();

    try {
        Webhook::verify($case['body'], vectorHeaders($case), webhookSecret(), now: $timestamp + 301);
    } catch (WebhookVerificationException $exception) {
        expect($exception->reason)->toBe(VerificationFailure::Timestamp)
            ->and($exception->getMessage())->toContain('300 seconds');

        return;
    }

    test()->fail('The delivery was accepted.');
});

it('lets the caller change the tolerance', function () {
    $case = webhookVectors()['post completed'][0];
    $timestamp = (int) $case['timestamp'];

    expect(Webhook::isValid($case['body'], $case['signature'], $case['timestamp'], webhookSecret(), tolerance: 10, now: $timestamp + 11))->toBeFalse()
        ->and(Webhook::isValid($case['body'], $case['signature'], $case['timestamp'], webhookSecret(), tolerance: 600, now: $timestamp + 500))->toBeTrue();
});

it('uses the clock when no time is given', function () {
    $body = '{"event":"post.created","data":{"id":1}}';
    $timestamp = (string) time();
    $signature = hash_hmac('sha256', $timestamp.'.'.$body, webhookSecret());

    expect(Webhook::isValid($body, $signature, $timestamp, webhookSecret()))->toBeTrue()
        ->and(Webhook::isValid($body, $signature, '1763456789', webhookSecret()))->toBeFalse();
});

it('gives the reason of a signature failure', function (?string $signature, ?string $timestamp) {
    $case = webhookVectors()['post created without a stage'][0];
    $headers = array_filter([
        'X-FeedbackPilgrim-Signature-V2' => $signature,
        'X-FeedbackPilgrim-Timestamp' => $timestamp,
    ]);

    try {
        Webhook::verify($case['body'], $headers, webhookSecret(), now: (int) $case['timestamp']);
    } catch (WebhookVerificationException $exception) {
        expect($exception->reason)->toBe(VerificationFailure::Signature);

        return;
    }

    test()->fail('The delivery was accepted.');
})->with([
    'no signature' => [null, '1763456789'],
    'no timestamp' => ['2d4194d96e8fbc9affc0ff86526ba3a073d622702e09b68608bbf1fc073688e6', null],
    'wrong signature' => [str_repeat('0', 64), '1763456789'],
]);

it('does not accept the deprecated body-only signature', function () {
    foreach (vectors('webhook-signature-vectors-legacy')['cases'] as $case) {
        expect(Webhook::isValid($case['body'], $case['signature'], '1763456789', webhookSecret(), now: 1763456789))->toBeFalse();
    }
});

it('rejects a signed body that is not a JSON object', function () {
    $timestamp = '1763456789';

    try {
        Webhook::verify('[1,2]', [
            'X-FeedbackPilgrim-Signature-V2' => hash_hmac('sha256', $timestamp.'.[1,2]', webhookSecret()),
            'X-FeedbackPilgrim-Timestamp' => $timestamp,
        ], webhookSecret(), now: (int) $timestamp);
    } catch (WebhookVerificationException $exception) {
        expect($exception->reason)->toBe(VerificationFailure::Payload);

        return;
    }

    test()->fail('The delivery was accepted.');
});

it('needs a secret', function () {
    Webhook::isValid('{}', 'a', '1', '');
})->throws(MissingCredentialsException::class, 'FEEDBACKPILGRIM_WEBHOOK_SECRET');
