<?php

declare(strict_types=1);

namespace Bitgen\Sdk;

use Bitgen\Sdk\Exception\BitgenException;
use Bitgen\Sdk\Exception\BitgenRawException;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

/**
 * Internal HTTP client. All requests go through here.
 * Not part of the public API — subject to change.
 */
class HttpClient
{
    private Client $guzzle;

    public function __construct(Config $config)
    {
        $this->guzzle = new Client([
            'base_uri' => $config->baseUrl,
            'headers'  => [
                'BITGEN-Scope' => $config->scope,
                'Api-key'      => $config->apiKey,
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'http_errors' => false,
        ]);
    }

    /**
     * @throws BitgenException
     */
    public function get(string $path, array $query = []): array
    {
        $options = [];
        if (!empty($query)) {
            $options[RequestOptions::QUERY] = $query;
        }

        $response = $this->guzzle->get($path, $options);
        $status   = $response->getStatusCode();
        $body     = (string) $response->getBody();

        return $this->parseStandard($status, $body);
    }

    /**
     * @throws BitgenException
     */
    public function post(string $path, array $payload = []): array
    {
        $response = $this->guzzle->post($path, [
            RequestOptions::JSON => $payload,
        ]);

        $status = $response->getStatusCode();
        $body   = (string) $response->getBody();

        return $this->parseStandard($status, $body);
    }

    /**
     * Used exclusively by TransactionResource::create().
     * The POST /api/v3/tx endpoint returns HTTP 500 / 400 as plain text.
     *
     * @throws BitgenRawException
     * @throws BitgenException
     */
    public function postRaw(string $path, array $payload = []): array
    {
        $response = $this->guzzle->post($path, [
            RequestOptions::JSON => $payload,
        ]);

        $status = $response->getStatusCode();
        $body   = (string) $response->getBody();

        // These two codes come back as plain text — not JSON
        if ($status === 500 || $status === 400) {
            throw new BitgenRawException($status, trim($body));
        }

        return $this->parseStandard($status, $body);
    }

    /**
     * @throws BitgenException
     */
    public function put(string $path, array $payload = []): array
    {
        $response = $this->guzzle->put($path, [
            RequestOptions::JSON => $payload,
        ]);

        $status = $response->getStatusCode();
        $body   = (string) $response->getBody();

        return $this->parseStandard($status, $body);
    }

    /**
     * @throws BitgenException
     */
    public function delete(string $path): array
    {
        $response = $this->guzzle->delete($path);
        $status   = $response->getStatusCode();
        $body     = (string) $response->getBody();

        return $this->parseStandard($status, $body);
    }

    /**
     * Decode JSON, surface any embedded error object as a BitgenException.
     *
     * @throws BitgenException
     */
    private function parseStandard(int $status, string $body): array
    {
        if ($body === '' || $body === 'null') {
            return [];
        }

        $decoded = json_decode($body, associative: true, flags: JSON_THROW_ON_ERROR);

        if (isset($decoded['error'])) {
            throw BitgenException::fromArray($status, $decoded['error']);
        }

        return $decoded;
    }
}
