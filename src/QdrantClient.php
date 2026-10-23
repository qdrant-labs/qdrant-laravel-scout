<?php

namespace QdrantLabs\LaravelScout;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\RequestException;
use Laravel\Scout\Exceptions\ScoutException;
use Throwable;

class QdrantClient
{
    /**
     * Create a new Qdrant client instance.
     */
    public function __construct(
        protected Factory $http,
        protected array $config
    ) {
        //
    }

    /**
     * Send a request to Qdrant.
     */
    public function request(string $method, string $uri, array $options = []): array
    {
        return $this->decode(
            $this->configure($this->http->createPendingRequest())->send($method, $uri, $options)
        );
    }

    /**
     * Send several requests to Qdrant at the same time.
     *
     * @param  array<string, array{0: string, 1: string, 2: array}>  $requests
     */
    public function concurrently(array $requests): array
    {
        $responses = $this->http->pool(function (Pool $pool) use ($requests) {
            foreach ($requests as $key => [$method, $uri, $options]) {
                $this->configure($pool->as($key))->send($method, $uri, $options);
            }
        });

        return array_map(fn ($response) => $this->decode($response), $responses);
    }

    /**
     * Decode a Qdrant response, throwing on failure.
     */
    protected function decode($response): array
    {
        if ($response instanceof Throwable) {
            throw $response;
        }

        return $response->throw()->json() ?? [];
    }

    /**
     * Configure a pending HTTP request for Qdrant.
     */
    protected function configure(PendingRequest $request): PendingRequest
    {
        $request
            ->baseUrl(rtrim($this->config['host'] ?? 'http://localhost:6333', '/'))
            ->withUserAgent('qdrant-laravel-scout')
            ->acceptJson()
            ->asJson()
            ->timeout($this->config['timeout'] ?? 60)
            ->connectTimeout($this->config['connect_timeout'] ?? 5);

        if (! empty($this->config['api_key'])) {
            $request->withHeaders(['api-key' => $this->config['api_key']]);
        }

        $retries = (int) ($this->config['retries'] ?? 3);

        if ($retries > 0) {
            $request->retry($retries + 1, 250, function ($exception) {
                return $exception instanceof ConnectionException ||
                    ($exception instanceof RequestException && in_array($exception->response->status(), [408, 429, 500, 502, 503, 504], true));
            });
        }

        return $request;
    }

    /**
     * Get a Qdrant collection instance.
     */
    public function collection(string $name): QdrantCollection
    {
        if (! preg_match('/^[A-Za-z0-9_.-]{1,255}$/', $name)) {
            throw new ScoutException("Invalid Qdrant collection [{$name}].");
        }

        return new QdrantCollection($this, $name);
    }
}
