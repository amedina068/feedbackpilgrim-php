<?php

declare(strict_types=1);

namespace FeedbackPilgrim\Exceptions;

/**
 * HTTP 404: the client of the token has no record with this ID.
 */
final class NotFoundException extends ApiException {}
