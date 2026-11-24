<?php

declare(strict_types=1);

namespace FeedbackPilgrim\Exceptions;

use FeedbackPilgrim\Webhooks\VerificationFailure;

/**
 * A webhook delivery is not valid. `$reason` tells why.
 *
 * Answer the delivery with HTTP 400, and do not process it.
 */
final class WebhookVerificationException extends FeedbackPilgrimException
{
    public function __construct(string $message, public readonly VerificationFailure $reason)
    {
        parent::__construct($message);
    }

    public static function invalidSignature(): self
    {
        return new self(
            'The webhook signature is missing or does not match the timestamp and the payload.',
            VerificationFailure::Signature,
        );
    }

    public static function invalidTimestamp(int $tolerance): self
    {
        return new self(
            "The webhook timestamp is missing, is not a Unix time, or is more than {$tolerance} seconds before or after the current time.",
            VerificationFailure::Timestamp,
        );
    }

    public static function invalidPayload(): self
    {
        return new self('The webhook payload is not a JSON object.', VerificationFailure::Payload);
    }
}
