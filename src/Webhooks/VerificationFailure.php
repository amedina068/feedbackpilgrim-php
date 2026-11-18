<?php

declare(strict_types=1);

namespace FeedbackPilgrim\Webhooks;

/**
 * The reason why a webhook delivery is not valid.
 */
enum VerificationFailure: string
{
    /**
     * The signature or the timestamp header is missing, or the signature does
     * not match the timestamp and the body.
     */
    case Signature = 'signature';

    /**
     * The timestamp is more than the tolerance before or after the current time.
     */
    case Timestamp = 'timestamp';

    /**
     * The signature is valid, but the body is not a JSON object.
     */
    case Payload = 'payload';
}
