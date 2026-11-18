<?php

declare(strict_types=1);

namespace FeedbackPilgrim\Exceptions;

/**
 * HTTP 405: the API does not support this operation.
 */
final class MethodNotAllowedException extends ApiException {}
