<?php

declare(strict_types=1);

namespace FeedbackPilgrim\Laravel;

use FeedbackPilgrim\FeedbackPilgrim;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

final class FeedbackPilgrimServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/feedbackpilgrim.php', 'feedbackpilgrim');

        $this->app->singleton(FeedbackPilgrim::class, static function (Application $app): FeedbackPilgrim {
            $config = $app->make(Repository::class);

            return new FeedbackPilgrim(
                url: self::string($config->get('feedbackpilgrim.url')) ?? '',
                token: self::string($config->get('feedbackpilgrim.token')),
                identitySecret: self::string($config->get('feedbackpilgrim.identity_secret')),
                timeout: is_numeric($timeout = $config->get('feedbackpilgrim.timeout')) ? (float) $timeout : 30.0,
                retries: is_numeric($retries = $config->get('feedbackpilgrim.retries')) ? (int) $retries : 2,
            );
        });

        $this->app->alias(FeedbackPilgrim::class, 'feedbackpilgrim');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/feedbackpilgrim.php' => $this->app->configPath('feedbackpilgrim.php'),
            ], 'feedbackpilgrim-config');
        }
    }

    private static function string(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
