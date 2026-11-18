<?php

declare(strict_types=1);

namespace FeedbackPilgrim\Laravel\Facades;

use FeedbackPilgrim\FeedbackPilgrim as Client;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \FeedbackPilgrim\Resources\Posts posts()
 * @method static \FeedbackPilgrim\Resources\ReleaseNotes releaseNotes()
 * @method static \FeedbackPilgrim\Resources\Contacts contacts()
 * @method static \FeedbackPilgrim\Resources\Testimonials testimonials()
 * @method static \FeedbackPilgrim\Resources\Tags tags()
 * @method static string|null signInUrl(string $email, string|null $name = null, float|null $mrr = null, float|null $ltv = null, string|null $avatar = null, array<string, mixed> $customFields = [])
 * @method static string identityHash(string $email)
 *
 * @see Client
 */
final class FeedbackPilgrim extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Client::class;
    }
}
