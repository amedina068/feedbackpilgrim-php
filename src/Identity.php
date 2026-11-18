<?php

declare(strict_types=1);

namespace FeedbackPilgrim;

use FeedbackPilgrim\Exceptions\MissingCredentialsException;
use SensitiveParameter;

/**
 * The identity hash (`user_hash`) that proves the email of the signed-in
 * user to the widget.
 *
 * Make the hash on your server only. Never put the identity secret in
 * browser code.
 */
final class Identity
{
    /**
     * The HMAC-SHA256 hex digest of the normalized email, with the identity
     * secret of the client as the key.
     *
     * The instance normalizes the email with `strtolower(trim($email))`. That
     * trim removes only spaces, tabs, line breaks, NUL and vertical tabs, and
     * that lowercase changes only the ASCII letters. The SDK does the same.
     */
    public static function hash(string $email, #[SensitiveParameter] string $secret): string
    {
        if ($secret === '') {
            throw MissingCredentialsException::identitySecret();
        }

        return hash_hmac('sha256', strtolower(trim($email)), $secret);
    }
}
