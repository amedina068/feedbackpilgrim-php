<?php

declare(strict_types=1);

namespace FeedbackPilgrim\Http;

use FeedbackPilgrim\Exceptions\ApiException;
use FeedbackPilgrim\Exceptions\ConnectionException;
use FeedbackPilgrim\Exceptions\MissingCredentialsException;
use FeedbackPilgrim\FeedbackPilgrim;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use SensitiveParameter;

/**
 * Sends requests to the public API of one instance, with the token and the
 * retry policy.
 *
 * The SDK sends each request again after a 429 or a connection error before
 * the send (a DNS failure, a refused connection or a connect timeout), because
 * the instance did not do the request. It sends an idempotent request again
 * after a 5xx, another connection error or a timeout.
 *
 * @internal
 */
final readonly class Transport
{
    public function __construct(
        private string $apiUrl,
        #[SensitiveParameter] private ?string $token,
        private ClientInterface $client,
        private RequestFactoryInterface $requests,
        private StreamFactoryInterface $streams,
        private RetryPolicy $retry,
    ) {}

    /**
     * @param  array<string, mixed>  $query
     */
    public function get(string $path, array $query = []): Response
    {
        return $this->send('GET', $path, query: $query, idempotent: true);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  bool  $idempotent  True when a second identical request has the same effect, for example an upsert.
     */
    public function post(string $path, array $data = [], bool $idempotent = false): Response
    {
        return $this->send('POST', $path, body: $this->encode($data), idempotent: $idempotent);
    }

    /**
     * Each PATCH of the public API sets fields to the given values. A second
     * identical request changes nothing, so the SDK can send it again.
     *
     * @param  array<string, mixed>  $data
     */
    public function patch(string $path, array $data): Response
    {
        return $this->send('PATCH', $path, body: $this->encode($data), idempotent: true);
    }

    public function delete(string $path): Response
    {
        return $this->send('DELETE', $path, idempotent: true);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function send(string $method, string $path, array $query = [], ?string $body = null, bool $idempotent = false): Response
    {
        $attempts = 0;

        while (true) {
            $attempts++;

            try {
                $response = Response::fromPsr(
                    $this->client->sendRequest($this->request($method, $path, $query, $body))
                );
            } catch (ClientExceptionInterface $exception) {
                $error = ConnectionException::from($exception);

                if ($this->retry->allows($attempts) && ($idempotent || $error->beforeSend)) {
                    $this->retry->sleep($this->retry->delay($attempts));

                    continue;
                }

                throw $error;
            }

            if ($response->status < 400) {
                return $response;
            }

            if ($this->shouldRetry($response, $attempts, $idempotent)) {
                $this->retry->sleep($this->retry->delay($attempts, $response->retryAfter()));

                continue;
            }

            throw ApiException::fromResponse($response);
        }
    }

    private function shouldRetry(Response $response, int $attempts, bool $idempotent): bool
    {
        if (! $this->retry->allows($attempts) || ! $this->retry->accepts($response->retryAfter())) {
            return false;
        }

        return $response->status === 429 || ($response->status >= 500 && $idempotent);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function request(string $method, string $path, array $query, ?string $body): RequestInterface
    {
        if ($this->token === null || $this->token === '') {
            throw MissingCredentialsException::token();
        }

        $url = $this->apiUrl.'/'.ltrim($path, '/');

        if ($query !== []) {
            $url .= '?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        // Without "Accept: application/json", Laravel answers an error with a
        // redirect to /login or with an HTML page.
        $request = $this->requests->createRequest($method, $url)
            ->withHeader('Authorization', 'Bearer '.$this->token)
            ->withHeader('Accept', 'application/json')
            ->withHeader('User-Agent', 'feedbackpilgrim-php/'.FeedbackPilgrim::VERSION);

        if ($body === null) {
            return $request;
        }

        return $request
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->streams->createStream($body));
    }

    /**
     * Keep the token out of `var_dump()` and `dd()` output.
     *
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return ['apiUrl' => $this->apiUrl, 'token' => '********'];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function encode(array $data): string
    {
        return $data === [] ? '{}' : json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
