<?php

declare(strict_types=1);

namespace FeedbackPilgrim\Exceptions;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\NetworkExceptionInterface;

/**
 * The SDK did not get a response from the instance.
 *
 * `$beforeSend` is true for a DNS failure, a refused connection or a connect
 * timeout. Then the instance did not get the request, and a retry is safe.
 */
final class ConnectionException extends FeedbackPilgrimException
{
    /**
     * cURL error 5 and 6 are DNS failures. cURL error 7 is a connection that
     * did not open. cURL error 28 with "Connection timed out" is a connect timeout.
     */
    private const BEFORE_SEND_PATTERN = '/cURL error (5|6|7):|cURL error 28: Connection timed out/i';

    public function __construct(string $message, public readonly bool $beforeSend = false, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    public static function from(ClientExceptionInterface $exception): self
    {
        $beforeSend = $exception instanceof NetworkExceptionInterface
            && preg_match(self::BEFORE_SEND_PATTERN, $exception->getMessage()) === 1;

        return new self(
            'Could not connect to the Feedback Pilgrim instance: '.$exception->getMessage(),
            $beforeSend,
            $exception,
        );
    }
}
