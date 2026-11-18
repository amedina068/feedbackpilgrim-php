<?php

declare(strict_types=1);

namespace FeedbackPilgrim\Exceptions;

use FeedbackPilgrim\Http\Response;

/**
 * The instance answered with an error status.
 *
 * The SDK throws a subclass for each known status. This class is used for the
 * other 4xx statuses.
 */
class ApiException extends FeedbackPilgrimException
{
    final public function __construct(
        string $message,
        public readonly int $status,
        public readonly Response $response,
    ) {
        parent::__construct($message, $status);
    }

    public static function fromResponse(Response $response): ApiException
    {
        $class = match (true) {
            $response->status === 401 => AuthenticationException::class,
            $response->status === 403 => AuthorizationException::class,
            $response->status === 404 => NotFoundException::class,
            $response->status === 405 => MethodNotAllowedException::class,
            $response->status === 422 => ValidationException::class,
            $response->status === 429 => RateLimitException::class,
            $response->status >= 500 => ServerException::class,
            default => ApiException::class,
        };

        return new $class(self::messageFrom($response), $response->status, $response);
    }

    /**
     * The decoded error body, or an empty array when the body is not JSON.
     *
     * @return array<array-key, mixed>
     */
    public function body(): array
    {
        return $this->response->jsonOrEmpty();
    }

    /**
     * Most errors have a `message` field. Some authentication errors have an
     * `error` field instead.
     */
    private static function messageFrom(Response $response): string
    {
        $body = $response->jsonOrEmpty();

        foreach (['message', 'error'] as $key) {
            if (isset($body[$key]) && is_string($body[$key]) && $body[$key] !== '') {
                return $body[$key];
            }
        }

        return "The Feedback Pilgrim instance answered with HTTP status {$response->status}.";
    }
}
