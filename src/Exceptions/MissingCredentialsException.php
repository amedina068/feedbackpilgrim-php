<?php

declare(strict_types=1);

namespace FeedbackPilgrim\Exceptions;

/**
 * A call needs a setting that the client does not have.
 */
final class MissingCredentialsException extends FeedbackPilgrimException
{
    public static function url(): self
    {
        return new self('Set the URL of your Feedback Pilgrim instance. In Laravel, set FEEDBACKPILGRIM_URL.');
    }

    public static function token(): self
    {
        return new self('Set an API token to call the Feedback Pilgrim API. In Laravel, set FEEDBACKPILGRIM_TOKEN.');
    }

    public static function identitySecret(): self
    {
        return new self('Set the identity secret of the client to make an identity hash. In Laravel, set FEEDBACKPILGRIM_IDENTITY_SECRET.');
    }

    public static function webhookSecret(): self
    {
        return new self('Set the signing secret of the webhook to verify a delivery. In Laravel, set FEEDBACKPILGRIM_WEBHOOK_SECRET.');
    }
}
