<?php

declare(strict_types=1);

use FeedbackPilgrim\Exceptions\MissingCredentialsException;
use FeedbackPilgrim\Identity;

it('matches each shared identity hash vector', function (array $case) {
    $secret = vectors('identity-hash-vectors')['identity_secret'];

    expect(Identity::hash($case['email'], $secret))->toBe($case['user_hash'])
        ->and(Identity::hash($case['normalized_email'], $secret))->toBe($case['user_hash'])
        ->and(pilgrim(identitySecret: $secret)->identityHash($case['email']))->toBe($case['user_hash']);
})->with(fn () => array_combine(
    array_column(vectors('identity-hash-vectors')['cases'], 'name'),
    array_map(fn (array $case) => [$case], vectors('identity-hash-vectors')['cases']),
));

it('trims only the characters of the PHP trim function', function () {
    $secret = 'secret';

    expect(Identity::hash("\0\x0B jane@example.com \r", $secret))->toBe(Identity::hash('jane@example.com', $secret))
        ->and(Identity::hash("\u{00A0}jane@example.com", $secret))->not->toBe(Identity::hash('jane@example.com', $secret));
});

it('needs an identity secret', function () {
    Identity::hash('jane@example.com', '');
})->throws(MissingCredentialsException::class, 'FEEDBACKPILGRIM_IDENTITY_SECRET');

it('needs an identity secret on the client', function () {
    pilgrim()->identityHash('jane@example.com');
})->throws(MissingCredentialsException::class, 'FEEDBACKPILGRIM_IDENTITY_SECRET');
