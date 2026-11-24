# Feedback Pilgrim PHP SDK

The PHP client and the Laravel integration for a self-hosted Feedback Pilgrim instance.

The SDK reads and writes the posts, release notes, contacts, testimonials and tags of one client. It also makes the identity hash of the widget and verifies signed webhooks.

## Supported versions

| Runtime | Versions |
|---|---|
| PHP | 8.2, 8.3, 8.4 |
| Laravel (optional) | 11, 12, 13 |

The SDK uses PSR-18 and PSR-17. It works with any PSR-18 HTTP client. If Guzzle is installed, the SDK uses Guzzle by default.

## Install

The package is not on Packagist. Install it from GitHub.

1. Add the GitHub repository to the `repositories` key of your `composer.json`:

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/amedina068/feedbackpilgrim-php" }
    ]
}
```

2. Require the `main` branch:

```bash
composer require feedbackpilgrim/feedbackpilgrim-php:dev-main guzzlehttp/guzzle
```

The repository is private, so you need read access and a GitHub token for Composer. The package has no tag yet, so the `:dev-main` constraint is necessary.

## Quick start

```php
use FeedbackPilgrim\FeedbackPilgrim;

$pilgrim = new FeedbackPilgrim(
    url: 'https://feedback.example.com',
    token: getenv('FEEDBACKPILGRIM_TOKEN'),
);

$post = $pilgrim->posts()->create('Add dark mode', 'Please add a dark theme.');

$pilgrim->posts()->update($post->id, status: 'in_progress');
```

## Configuration

Each user runs an own instance, so the instance URL is required.

```php
use FeedbackPilgrim\FeedbackPilgrim;

$pilgrim = new FeedbackPilgrim(
    url: 'https://feedback.example.com',  // The APP_URL of your instance.
    token: $token,                         // An API token. Make one in Settings, API Tokens.
    identitySecret: $identitySecret,       // Optional. Only for identityHash().
    timeout: 30.0,                         // Seconds. The default is 30.
    retries: 2,                            // Or a RetryPolicy. The default is 2.
    httpClient: $psr18Client,              // Optional. Any PSR-18 client.
);
```

| Setting | Default | Description |
|---|---|---|
| `url` | (required) | The URL of your instance, with or without a trailing slash. |
| `token` | `null` | A Laravel Sanctum token, `<id>\|<random>`. It belongs to one client and does not expire. |
| `identitySecret` | `null` | The identity secret of the client. |
| `timeout` | `30.0` | The SDK uses it when it creates a Guzzle client. If you give your own client, set its timeout yourself. |
| `retries` | `2` | The number of retries after the first attempt. |

The SDK sends `Authorization: Bearer <token>` and `Accept: application/json` with each request. The token can read and change all the data of its client. Keep it on your server.

## Posts

```php
use FeedbackPilgrim\Data\Post;

$page = $pilgrim->posts()->list(status: Post::PENDING, tag: 'feature', perPage: 50);

foreach ($pilgrim->posts()->all(published: true) as $post) {
    echo $post->title;
}

$post = $pilgrim->posts()->find(42);

$post = $pilgrim->posts()->create(
    title: 'Add dark mode',
    content: 'Please add a dark theme.',
    status: Post::PENDING,
    boardId: 1,
    tags: [1, 2],
);

$post = $pilgrim->posts()->update(42, status: Post::COMPLETED, tags: [3]);
```

- Without `boardId`, the post goes on the first board of the client.
- A new status moves the post to the first stage of that type. Then the instance sends the same emails, webhooks and Slack message as the dashboard.
- `tags` replaces all the tags of the post.
- `$post->isPublished()` is true when the post has a publication date.
- `$post->stage` is in the create response and in some update responses. `$post->stage?->isComplete()` is true for a stage of the type `complete`.
- The API cannot delete a post. The SDK has no delete method for posts.

## Release notes

```php
$page = $pilgrim->releaseNotes()->list(published: true, sortBy: 'published_at');

foreach ($pilgrim->releaseNotes()->all(tag: 'mobile') as $note) {
    echo $note->title;
}

$note = $pilgrim->releaseNotes()->find(7);

$note = $pilgrim->releaseNotes()->create('Version 2.1.0', "## New\n- Dark mode", published: true, tags: [1]);

$note = $pilgrim->releaseNotes()->update(7, title: 'Version 2.1.1', published: false);
```

- Without `published: true`, a new note is a draft. The token user becomes the author.
- `published: true` keeps a publication date that exists. `published: false` clears it.
- `$note->isPublished()` is true when the note has a publication date.
- The API cannot delete a release note.

## Contacts

```php
$page = $pilgrim->contacts()->list(search: 'jane', sortBy: 'mrr');

foreach ($pilgrim->contacts()->all(tag: 'vip') as $contact) {
    echo $contact->email;
}

$contact = $pilgrim->contacts()->find(7);

$contact = $pilgrim->contacts()->upsert(
    email: 'jane@example.com',
    name: 'Jane Smith',
    mrr: 149.99,
    tags: [1],
    customFields: ['plan' => 'pro'],
);

$contact = $pilgrim->contacts()->update(7, name: 'Jane S.', mrr: 199);
```

- `upsert()` creates a contact, or updates the contact of the client with the same email.
- `upsert()` merges the custom fields with the custom fields of the contact. The limits are 50 keys, 3 levels and 16 KB. A key cannot start with `pilgrim_`.
- `update()` does not change the custom fields. To change them, call `upsert()` with the email of the contact.
- `$contact->customFields` holds the custom fields. The SDK reads them from a contact of the Contacts operations and from a contact inside a post or a testimonial.
- The API cannot delete a contact.

## Testimonials

```php
$pending = $pilgrim->testimonials()->list();
$approved = $pilgrim->testimonials()->list(approved: true, rating: 5);

foreach ($pilgrim->testimonials()->all(approved: true) as $testimonial) {
    echo $testimonial->description;
}

$testimonial = $pilgrim->testimonials()->find(3);

$testimonial = $pilgrim->testimonials()->create('The roadmap keeps our customers in the loop.', 'jane_doe', rating: 5, contactId: 7);

$testimonial = $pilgrim->testimonials()->update(3, rating: 4, approvedAt: '2026-06-15T10:00:00Z');

$testimonial = $pilgrim->testimonials()->approve(3);
```

- `list()` and `all()` give the pending testimonials by default. Use `approved: true` for the approved testimonials.
- A new testimonial is pending. `approve()` sets the approval date to now, or to the date that you give.
- `$testimonial->isApproved()` is true when the testimonial has an approval date.
- The API cannot delete a testimonial, and it cannot clear an approval date.

## Tags

```php
$page = $pilgrim->tags()->list(search: 'feat');

foreach ($pilgrim->tags()->all() as $tag) {
    echo "{$tag->title}: {$tag->postsCount} posts";
}

$tag = $pilgrim->tags()->create('feature');

$tag = $pilgrim->tags()->update($tag->id, 'enhancement');

$pilgrim->tags()->delete($tag->id);
```

- The title of a tag must be unique in the client.
- `delete()` removes the tag from each post, release note and contact. Then it deletes the tag.

## Sign-in link

`signInUrl()` creates or updates the contact with this email. Then it returns a sign-in link for the public site of the client.

```php
$url = $pilgrim->signInUrl('jane@example.com', name: 'Jane Doe', customFields: ['plan' => 'pro']);
```

- The link is valid for 24 hours. Send it only to that contact.
- For a contact that exists, the custom fields of this call replace the custom fields of the contact.
- The method returns `null` when the instance sends no link. For example, an empty email gets `[]` and no link. The method does not throw an exception for it.
- The SDK does not send this request again after a 5xx or a timeout, because each call makes a new link.

## Identity hash

The widget proves the email of the signed-in user with `user_hash`. Make the hash on your server. Never put the identity secret in browser code.

```php
use FeedbackPilgrim\Identity;

$userHash = Identity::hash($user->email, $identitySecret);

// Or with the identity secret of the client:
$userHash = $pilgrim->identityHash($user->email);
```

The hash is the HMAC-SHA256 hex digest of `strtolower(trim($email))`, with the identity secret as the key. Give only the hash and the email to the page. The secret stays on the server.

## Webhooks

The instance signs each delivery. `Webhook::isValid()` gives a boolean. `Webhook::verify()` also gives the event, or throws an exception.

```php
use FeedbackPilgrim\Webhooks\Webhook;

$valid = Webhook::isValid(
    payload: $rawBody,
    signature: $_SERVER['HTTP_X_FEEDBACKPILGRIM_SIGNATURE_V2'] ?? null,
    timestamp: $_SERVER['HTTP_X_FEEDBACKPILGRIM_TIMESTAMP'] ?? null,
    secret: $webhookSecret,
);
```

```php
use FeedbackPilgrim\Exceptions\WebhookVerificationException;
use FeedbackPilgrim\Webhooks\Webhook;
use FeedbackPilgrim\Webhooks\WebhookEvent;

try {
    $event = Webhook::verify($rawBody, getallheaders(), $webhookSecret, tolerance: 300);
} catch (WebhookVerificationException $exception) {
    http_response_code(400); // $exception->reason tells why.
    exit;
}

if ($event->is(WebhookEvent::POST_COMPLETED)) {
    notifyVoters($event->data['id'], $event->deliveryId);
}
```

- Give the raw request body, byte for byte. Do not decode the JSON and encode it again. The instance escapes `/` and each character that is not ASCII, so a new encoding gives other bytes.
- The SDK verifies the `X-FeedbackPilgrim-Signature-V2` header: the HMAC-SHA256 hex digest of `<timestamp>.<raw body>`. It makes the header lowercase, then compares the digests in constant time. An uppercase hex signature is valid.
- The SDK rejects a timestamp that is more than 300 seconds from the current time. Change the limit with `tolerance`.
- The event has `$type`, `$data`, `$payload` (the decoded body), `$timestamp` (an integer) and `$deliveryId`.
- If the `X-FeedbackPilgrim-Delivery` header is missing or empty, the verification passes and `$deliveryId` is `null`. Older instances do not send this header.
- `$exception->reason` is a `VerificationFailure`. The SDK does the checks in this order, and the first check that fails gives the reason:
  1. `VerificationFailure::Timestamp`: the timestamp header is missing or is not digits.
  2. `VerificationFailure::Signature`: the signature header is missing or does not match.
  3. `VerificationFailure::Timestamp`: the timestamp is outside the tolerance.
  4. `VerificationFailure::Payload`: the body is not a JSON object.
- The enum values are `signature`, `timestamp` and `payload`, the same names as in the JS, Python and Ruby SDKs.
- The events are `post.created`, `post.status_changed` and `post.completed`. `$event->data` has `id`, `title`, `content`, `status`, `board_id`, `contact_id` and `stage`.
- Each retry of a delivery sends the same `$event->deliveryId`. Store the IDs that you processed, and ignore a repeated delivery. The SDK does not store IDs.
- The SDK does not verify the deprecated `X-FeedbackPilgrim-Signature` header.

## Errors

Each exception extends `FeedbackPilgrim\Exceptions\FeedbackPilgrimException`.

| Exception | Cause |
|---|---|
| `AuthenticationException` | 401. The token is missing or wrong, or the user is deactivated. |
| `AuthorizationException` | 403. The REST API does not send it today. |
| `NotFoundException` | 404. The client has no record with this ID. |
| `MethodNotAllowedException` | 405. The API does not support this operation. |
| `ValidationException` | 422. The instance refused the input. |
| `RateLimitException` | 429. The user sent more than 120 requests in one minute. |
| `ServerException` | 5xx. |
| `ApiException` | The parent of the HTTP exceptions above, and the class for another 4xx status. |
| `ConnectionException` | The SDK got no response. |
| `UnexpectedResponseException` | The body is not the JSON that the SDK expects. |
| `MissingCredentialsException` | A required setting is missing. |
| `WebhookVerificationException` | A webhook delivery is not valid. |

```php
use FeedbackPilgrim\Exceptions\ApiException;
use FeedbackPilgrim\Exceptions\RateLimitException;
use FeedbackPilgrim\Exceptions\ValidationException;

try {
    $pilgrim->tags()->create('feature');
} catch (ValidationException $exception) {
    $exception->errors();        // ['title' => ['The title has already been taken.']]
    $exception->first('title');  // 'The title has already been taken.'
} catch (RateLimitException $exception) {
    $exception->retryAfter();    // Seconds, from Retry-After.
    $exception->limit();         // 120, from X-RateLimit-Limit.
    $exception->remaining();     // 0, from X-RateLimit-Remaining.
} catch (ApiException $exception) {
    $exception->status;          // The HTTP status.
    $exception->body();          // The decoded error body.
}
```

## Retries

The SDK sends a request again up to 2 times by default.

- The delay grows exponentially, with jitter: 250 to 500 ms, then 500 to 1000 ms, up to 8 seconds.
- After a 429, the SDK sends each request again, because the instance did not do the request.
- After a connection error before the send, the SDK sends each request again, also a create. The instance did not get the request. These errors are a DNS failure (cURL error 5 or 6), a connection that did not open (cURL error 7) and a connect timeout (cURL error 28, "Connection timed out"). `ConnectionException::$beforeSend` is true for them.
- After a 5xx, a read timeout, a reset or another connection error, the SDK sends only an idempotent request again.
- If a retried response has `Retry-After` (seconds or an HTTP date), the SDK waits that time. If that time is more than 60 seconds, the SDK throws the exception.
- These calls are idempotent: each `list()`, `all()` and `find()`, each `update()`, `testimonials()->approve()`, `contacts()->upsert()` and `tags()->delete()`.
- These calls are not idempotent: `signInUrl()` and each `create()`.
- The limit is 120 requests each minute for each user. All the tokens of one user share it.
- The JS, Python and Ruby SDKs use the same retry rules.

```php
use FeedbackPilgrim\FeedbackPilgrim;
use FeedbackPilgrim\Http\RetryPolicy;

$pilgrim = new FeedbackPilgrim($url, $token, retries: new RetryPolicy(retries: 3, baseDelayMs: 200, maxDelayMs: 5_000, maxRetryAfter: 30));

$pilgrim = new FeedbackPilgrim($url, $token, retries: RetryPolicy::none());
```

## Pagination

Each `list()` method returns one `Page`. Each `all()` method returns a generator that reads the pages one by one.

```php
$page = $pilgrim->posts()->list(perPage: 100, page: 2);

$page->items;        // list<Post>
$page->currentPage;  // 2
$page->lastPage;
$page->total;
$page->hasMore();    // True when a next page exists.
$page->nextPage();   // The next page number, or null.
$page->isEmpty();
$page->first();      // The first item, or null.
count($page);

foreach ($page as $post) {
    echo $post->title;
}

foreach ($pilgrim->contacts()->all(perPage: 100) as $contact) {
    echo $contact->email;
}
```

- `per_page` is 1 to 100. The default of `list()` is 15. The default of `all()` is 100.
- The SDK reads the next page only when your loop gets to it.
- The lists use page numbers. If records change while you read, a record can move to another page.

## Laravel

The package registers the service provider and the `FeedbackPilgrim` facade automatically.

1. Add the settings to your `.env` file:

```dotenv
FEEDBACKPILGRIM_URL=https://feedback.example.com
FEEDBACKPILGRIM_TOKEN=
FEEDBACKPILGRIM_IDENTITY_SECRET=
FEEDBACKPILGRIM_WEBHOOK_SECRET=
```

2. Optional: publish the configuration file to `config/feedbackpilgrim.php`:

```bash
php artisan vendor:publish --tag=feedbackpilgrim-config
```

3. Use the facade, or inject the client:

```php
use FeedbackPilgrim\FeedbackPilgrim as Client;
use FeedbackPilgrim\Laravel\Facades\FeedbackPilgrim;

$posts = FeedbackPilgrim::posts()->list(status: 'pending');

$userHash = FeedbackPilgrim::identityHash($request->user()->email);

Route::get('/changelog', fn (Client $pilgrim) => $pilgrim->releaseNotes()->list(published: true)->items);
```

The configuration file has these keys: `url`, `token`, `identity_secret`, `webhook.secret`, `webhook.tolerance`, `timeout` and `retries`.

To receive webhooks, add a route to `routes/api.php`. The `api` routes have no CSRF check.

```php
use FeedbackPilgrim\Exceptions\WebhookVerificationException;
use FeedbackPilgrim\Webhooks\Webhook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

Route::post('/webhooks/feedback-pilgrim', function (Request $request) {
    try {
        $event = Webhook::verify(
            $request->getContent(),
            $request->headers->all(),
            config('feedbackpilgrim.webhook.secret'),
            config('feedbackpilgrim.webhook.tolerance'),
        );
    } catch (WebhookVerificationException) {
        abort(400);
    }

    if (Cache::add("feedbackpilgrim-delivery:{$event->deliveryId}", true, now()->addDay())) {
        ProcessFeedbackEvent::dispatch($event->type, $event->data);
    }

    return response()->noContent();
});
```

## Development

```bash
composer test        # Unit and Laravel tests.
composer analyse     # PHPStan at the max level.
composer lint        # Pint, in test mode.
composer format      # Pint.
```

The integration tests run only when `FEEDBACKPILGRIM_TEST_URL` and `FEEDBACKPILGRIM_TEST_TOKEN` are set. They are read-only: they list the posts and the tags.

```bash
FEEDBACKPILGRIM_TEST_URL=https://feedback.example.com FEEDBACKPILGRIM_TEST_TOKEN=... composer test:integration
```

## Security

To report a security problem, read [SECURITY.md](SECURITY.md).

## License

MIT. Read [LICENSE](LICENSE). The Feedback Pilgrim app has its own license.
