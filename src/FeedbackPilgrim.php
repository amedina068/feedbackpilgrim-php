<?php

declare(strict_types=1);

namespace FeedbackPilgrim;

use FeedbackPilgrim\Exceptions\MissingCredentialsException;
use FeedbackPilgrim\Http\HttpClientFactory;
use FeedbackPilgrim\Http\RetryPolicy;
use FeedbackPilgrim\Http\Transport;
use FeedbackPilgrim\Resources\Contacts;
use FeedbackPilgrim\Resources\Posts;
use FeedbackPilgrim\Resources\ReleaseNotes;
use FeedbackPilgrim\Resources\Tags;
use FeedbackPilgrim\Resources\Testimonials;
use Http\Discovery\Psr17FactoryDiscovery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use SensitiveParameter;

/**
 * The client for one Feedback Pilgrim instance.
 *
 *     $pilgrim = new FeedbackPilgrim(url: 'https://feedback.example.com', token: $token);
 *
 *     $post = $pilgrim->posts()->create('Add dark mode', 'Please add a dark theme.');
 */
class FeedbackPilgrim
{
    public const VERSION = '0.1.0';

    /**
     * The URL of the instance, with no trailing slash.
     */
    public readonly string $url;

    protected readonly Transport $transport;

    /**
     * @param  string  $url  The URL of your instance (its `APP_URL`).
     * @param  string|null  $token  An API token of the client. Make one in Settings, API Tokens.
     * @param  string|null  $identitySecret  The identity secret of the client, for `identityHash()`.
     * @param  float  $timeout  The request timeout in seconds. The SDK uses it when it creates the HTTP client.
     * @param  int|RetryPolicy  $retries  The number of retries, or a full retry policy.
     * @param  ClientInterface|null  $httpClient  Any PSR-18 client. If you give one, configure its timeout yourself.
     */
    public function __construct(
        string $url,
        #[SensitiveParameter] ?string $token = null,
        #[SensitiveParameter] private readonly ?string $identitySecret = null,
        float $timeout = 30.0,
        int|RetryPolicy $retries = 2,
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
    ) {
        $this->url = rtrim(trim($url), '/');

        if ($this->url === '') {
            throw MissingCredentialsException::url();
        }

        $this->transport = new Transport(
            apiUrl: $this->url.'/api/public/v1',
            token: $token,
            client: $httpClient ?? HttpClientFactory::make($timeout),
            requests: $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory(),
            streams: $streamFactory ?? Psr17FactoryDiscovery::findStreamFactory(),
            retry: $retries instanceof RetryPolicy ? $retries : new RetryPolicy(retries: max(0, $retries)),
        );
    }

    public function posts(): Posts
    {
        return new Posts($this->transport);
    }

    public function releaseNotes(): ReleaseNotes
    {
        return new ReleaseNotes($this->transport);
    }

    public function contacts(): Contacts
    {
        return new Contacts($this->transport);
    }

    public function testimonials(): Testimonials
    {
        return new Testimonials($this->transport);
    }

    public function tags(): Tags
    {
        return new Tags($this->transport);
    }

    /**
     * Create or update the contact with this email, and return a sign-in link
     * for the public site of the client. The link is valid for 24 hours.
     * Send it only to that contact.
     *
     * For a contact that exists, the custom fields of this call replace the
     * custom fields of the contact.
     *
     * @param  array<string, mixed>  $customFields
     * @return string|null The link, or null when the instance gets no email.
     */
    public function signInUrl(
        string $email,
        ?string $name = null,
        ?float $mrr = null,
        ?float $ltv = null,
        ?string $avatar = null,
        array $customFields = [],
    ): ?string {
        $contact = array_filter([
            'email' => $email,
            'name' => $name,
            'mrr' => $mrr,
            'ltv' => $ltv,
            'avatar' => $avatar,
        ], static fn (mixed $value): bool => $value !== null) + $customFields;

        // Without a contact email, the instance answers with an empty JSON array.
        $url = $this->transport->post('init', ['contact' => $contact])->json()['auth_url'] ?? null;

        return is_string($url) ? $url : null;
    }

    /**
     * The identity hash (`user_hash`) of an email, for the widget.
     *
     * Make the hash on your server only. Never put the identity secret in
     * browser code.
     */
    public function identityHash(string $email): string
    {
        if ($this->identitySecret === null || $this->identitySecret === '') {
            throw MissingCredentialsException::identitySecret();
        }

        return Identity::hash($email, $this->identitySecret);
    }

    /**
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return ['url' => $this->url];
    }
}
