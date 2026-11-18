<?php

declare(strict_types=1);

namespace FeedbackPilgrim\Tests\Laravel;

use FeedbackPilgrim\Laravel\Facades\FeedbackPilgrim;
use FeedbackPilgrim\Laravel\FeedbackPilgrimServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [FeedbackPilgrimServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return ['FeedbackPilgrim' => FeedbackPilgrim::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('feedbackpilgrim.url', 'https://feedback.test');
        $app['config']->set('feedbackpilgrim.token', '1|test-token');
        $app['config']->set('feedbackpilgrim.identity_secret', 'test-identity-secret-0123456789abcdef');
    }
}
