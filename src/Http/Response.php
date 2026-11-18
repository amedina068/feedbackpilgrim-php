<?php

declare(strict_types=1);

namespace FeedbackPilgrim\Http;

use DateTimeImmutable;
use DateTimeZone;
use FeedbackPilgrim\Exceptions\UnexpectedResponseException;
use Psr\Http\Message\ResponseInterface;

/**
 * One HTTP response from the instance, with the body already read.
 */
final readonly class Response
{
    /**
     * @param  array<string, list<string>>  $headers  The header names are in lower case.
     */
    public function __construct(
        public int $status,
        public string $body,
        public array $headers = [],
    ) {}

    public static function fromPsr(ResponseInterface $response): self
    {
        $headers = [];

        foreach ($response->getHeaders() as $name => $values) {
            $headers[strtolower((string) $name)] = array_values($values);
        }

        return new self($response->getStatusCode(), (string) $response->getBody(), $headers);
    }

    /**
     * The decoded JSON body.
     *
     * @return array<array-key, mixed>
     */
    public function json(): array
    {
        $decoded = json_decode($this->body, true);

        if (! is_array($decoded)) {
            throw UnexpectedResponseException::invalidJson($this->status);
        }

        return $decoded;
    }

    /**
     * The decoded JSON body, or an empty array when the body is not JSON.
     *
     * @return array<array-key, mixed>
     */
    public function jsonOrEmpty(): array
    {
        $decoded = json_decode($this->body, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)][0] ?? null;
    }

    /**
     * A header that holds a whole number, or null when the header is missing
     * or holds another value.
     */
    public function intHeader(string $name): ?int
    {
        $value = trim($this->header($name) ?? '');

        return ctype_digit($value) ? (int) $value : null;
    }

    /**
     * The `Retry-After` header in seconds. The header can hold seconds or an
     * HTTP date.
     */
    public function retryAfter(): ?int
    {
        $value = trim($this->header('Retry-After') ?? '');

        if ($value === '') {
            return null;
        }

        if (ctype_digit($value)) {
            return (int) $value;
        }

        $date = DateTimeImmutable::createFromFormat('D, d M Y H:i:s \G\M\T', $value, new DateTimeZone('UTC'));

        return $date === false ? null : max(0, $date->getTimestamp() - time());
    }
}
