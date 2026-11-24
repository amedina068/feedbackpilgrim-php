<?php

declare(strict_types=1);

namespace FeedbackPilgrim\Webhooks;

use FeedbackPilgrim\Exceptions\MissingCredentialsException;
use FeedbackPilgrim\Exceptions\WebhookVerificationException;
use SensitiveParameter;

/**
 * Verifies the signed webhook deliveries of a Feedback Pilgrim instance.
 *
 * The instance signs `<timestamp>.<raw body>` with the signing secret of the
 * webhook, and sends the HMAC-SHA256 hex digest in the
 * `X-FeedbackPilgrim-Signature-V2` header.
 *
 * Give the raw request body, byte for byte. Do not decode and encode the JSON
 * again: the instance escapes "/" and each character that is not ASCII, so a
 * new encoding gives other bytes.
 */
final class Webhook
{
    public const SIGNATURE_HEADER = 'X-FeedbackPilgrim-Signature-V2';

    public const TIMESTAMP_HEADER = 'X-FeedbackPilgrim-Timestamp';

    public const DELIVERY_HEADER = 'X-FeedbackPilgrim-Delivery';

    public const EVENT_HEADER = 'X-FeedbackPilgrim-Event';

    public const DEFAULT_TOLERANCE = 300;

    /**
     * True when the signature matches the timestamp and the body, and the
     * timestamp is inside the tolerance.
     *
     * @param  string  $payload  The raw request body.
     * @param  string|null  $signature  The `X-FeedbackPilgrim-Signature-V2` header.
     * @param  string|null  $timestamp  The `X-FeedbackPilgrim-Timestamp` header.
     * @param  int  $tolerance  The maximum difference in seconds between the timestamp and now.
     * @param  int|null  $now  The current Unix time. Tests give it. The default is the clock.
     */
    public static function isValid(
        string $payload,
        ?string $signature,
        ?string $timestamp,
        #[SensitiveParameter] string $secret,
        int $tolerance = self::DEFAULT_TOLERANCE,
        ?int $now = null,
    ): bool {
        return self::failure($payload, $signature, $timestamp, $secret, $tolerance, $now) === null;
    }

    /**
     * Verify a delivery and return its event, with the delivery ID.
     *
     * The delivery ID is null when the delivery header is missing. Older
     * instances do not send it.
     *
     * @param  string  $payload  The raw request body.
     * @param  array<string, string|list<string|null>|null>  $headers  The request headers. The names ignore case.
     *
     * @throws WebhookVerificationException
     */
    public static function verify(
        string $payload,
        array $headers,
        #[SensitiveParameter] string $secret,
        int $tolerance = self::DEFAULT_TOLERANCE,
        ?int $now = null,
    ): WebhookEvent {
        $headers = array_change_key_case($headers);
        $timestamp = self::header($headers, self::TIMESTAMP_HEADER);

        $failure = self::failure($payload, self::header($headers, self::SIGNATURE_HEADER), $timestamp, $secret, $tolerance, $now);

        if ($failure === VerificationFailure::Timestamp) {
            throw WebhookVerificationException::invalidTimestamp($tolerance);
        }

        if ($failure === VerificationFailure::Signature) {
            throw WebhookVerificationException::invalidSignature();
        }

        $body = json_decode($payload, true);

        // "{}" and "[]" both decode to an empty array, so ask for an object too.
        if (! is_array($body) || ! json_decode($payload) instanceof \stdClass) {
            throw WebhookVerificationException::invalidPayload();
        }

        return WebhookEvent::fromDelivery(
            $body,
            (int) $timestamp,
            self::header($headers, self::DELIVERY_HEADER),
        );
    }

    private static function failure(
        string $payload,
        ?string $signature,
        ?string $timestamp,
        #[SensitiveParameter] string $secret,
        int $tolerance,
        ?int $now,
    ): ?VerificationFailure {
        if ($secret === '') {
            throw MissingCredentialsException::webhookSecret();
        }

        $signature = strtolower(trim($signature ?? ''));
        $timestamp = trim($timestamp ?? '');

        if (! ctype_digit($timestamp)) {
            return VerificationFailure::Timestamp;
        }

        // Check the signature before the clock, so that an unsigned request learns nothing about the clock.
        if ($signature === '' || ! hash_equals(hash_hmac('sha256', $timestamp.'.'.$payload, $secret), $signature)) {
            return VerificationFailure::Signature;
        }

        if (abs(($now ?? time()) - (int) $timestamp) > $tolerance) {
            return VerificationFailure::Timestamp;
        }

        return null;
    }

    /**
     * @param  array<string, string|list<string|null>|null>  $headers
     */
    private static function header(array $headers, string $name): ?string
    {
        $value = $headers[strtolower($name)] ?? null;

        if (is_array($value)) {
            $value = $value[0] ?? null;
        }

        $value = is_string($value) ? trim($value) : '';

        return $value === '' ? null : $value;
    }
}
