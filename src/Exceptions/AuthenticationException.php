<?php

declare(strict_types=1);

namespace FeedbackPilgrim\Exceptions;

/**
 * HTTP 401: the API token is missing or wrong, the user is deactivated, or the user left the client of the token.
 */
final class AuthenticationException extends ApiException {}
