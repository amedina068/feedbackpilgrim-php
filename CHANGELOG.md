# Changelog

All notable changes to this project are in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/). The project uses [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- The MIT license: `LICENSE` and `"license": "MIT"` in `composer.json`.
- The README gives the install from GitHub with the VCS repository and `:dev-main`.

### Changed

The SDK now behaves the same as the JS, Python and Ruby SDKs. These changes break code that uses the package from git:

- `Webhook::verify()` gives the reason `Timestamp` when the timestamp header is missing or is not digits. Before, a missing header gave `Signature`. The SDK checks the timestamp format before the signature.
- `Webhook::verify()` gives a null `$deliveryId` when the delivery header is empty. Before, it gave an empty string.
- `Webhook::verify()` accepts `{}` as the body. Before, it gave the reason `Payload`.
- `WebhookEvent::$type` comes from the signed body only. The SDK does not read the unsigned `X-FeedbackPilgrim-Event` header. `WebhookEvent::fromDelivery()` has no `$eventHeader` parameter.
- `ConnectionException::$requestSent` is now `$beforeSend`, with the opposite value. It is true only for cURL error 5, 6 and 7, and for cURL error 28 with "Connection timed out". Before, it also matched other texts, for example "failed to connect" without a cURL code. The SDK sends a request that is not idempotent again only after a 429 or one of these errors.
- `WebhookVerificationException::timestampOutsideTolerance()` is now `invalidTimestamp()`.

## [0.1.0] - Unreleased

### Added

- A client for one Feedback Pilgrim instance, with an instance URL, an API token, a timeout and a retry policy.
- Resources: posts, release notes, contacts, testimonials and tags. The SDK has no delete method for posts, release notes and testimonials, because the API always returns 405 for them.
- `signInUrl()`: the sign-in link of a contact, from `POST /api/public/v1/init`.
- Typed, read-only value objects for each response. A contact gives its custom fields in `customFields`, from the flat form and from the `metadata` string of a nested contact.
- `Identity::hash()` and `identityHash()`: the `user_hash` of the widget.
- `Webhook::isValid()` and `Webhook::verify()`: the V2 webhook signature, with a tolerance of 300 seconds by default and the delivery ID.
- A typed exception for each error: 401, 403, 404, 405, 422, 429, 5xx, connection and unexpected response.
- Retries after a 429, a 5xx or a connection error, with exponential backoff, jitter and `Retry-After`.
- `list()` returns one page. `all()` returns a generator that reads every page.
- PSR-18 and PSR-17 support with `php-http/discovery`.
- Laravel: a service provider with auto-discovery, a configuration file and a facade.
