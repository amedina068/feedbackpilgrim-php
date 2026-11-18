<?php

declare(strict_types=1);

use FeedbackPilgrim\Exceptions\MissingCredentialsException;
use FeedbackPilgrim\FeedbackPilgrim;
use FeedbackPilgrim\Laravel\Facades\FeedbackPilgrim as FeedbackPilgrimFacade;
use FeedbackPilgrim\Laravel\FeedbackPilgrimServiceProvider;
use Illuminate\Support\ServiceProvider;

it('merges the package configuration', function () {
    expect(config('feedbackpilgrim.timeout'))->toBe(30.0)
        ->and(config('feedbackpilgrim.retries'))->toBe(2)
        ->and(config('feedbackpilgrim.webhook.tolerance'))->toBe(300)
        ->and(config('feedbackpilgrim.url'))->toBe('https://feedback.test');
});

it('binds one client for the application', function () {
    $client = app(FeedbackPilgrim::class);

    expect($client)->toBeInstanceOf(FeedbackPilgrim::class)
        ->and(app('feedbackpilgrim'))->toBe($client)
        ->and($client->url)->toBe('https://feedback.test');
});

it('resolves the facade', function () {
    expect(FeedbackPilgrimFacade::getFacadeRoot())->toBe(app(FeedbackPilgrim::class));
});

it('makes the identity hash with the configured secret', function () {
    expect(FeedbackPilgrimFacade::identityHash('jane@example.com'))
        ->toBe('cd030afda18c4024c95fb274f9ac2441ebc165922a7211ddba16910915227ea4');
});

it('publishes the configuration file', function () {
    $paths = ServiceProvider::pathsToPublish(FeedbackPilgrimServiceProvider::class, 'feedbackpilgrim-config');

    expect($paths)->toHaveCount(1)
        ->and(array_key_first($paths))->toEndWith('config/feedbackpilgrim.php')
        ->and(reset($paths))->toBe(config_path('feedbackpilgrim.php'));
});

it('explains a missing instance URL', function () {
    config(['feedbackpilgrim.url' => null]);
    app()->forgetInstance(FeedbackPilgrim::class);

    app(FeedbackPilgrim::class);
})->throws(MissingCredentialsException::class, 'FEEDBACKPILGRIM_URL');

it('explains a missing token on the first request', function () {
    config(['feedbackpilgrim.token' => null]);
    app()->forgetInstance(FeedbackPilgrim::class);

    FeedbackPilgrimFacade::tags()->list();
})->throws(MissingCredentialsException::class, 'FEEDBACKPILGRIM_TOKEN');
