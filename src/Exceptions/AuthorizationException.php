<?php

declare(strict_types=1);

namespace FeedbackPilgrim\Exceptions;

/**
 * HTTP 403: the token cannot do this operation. The REST API does not send this status today.
 */
final class AuthorizationException extends ApiException {}
