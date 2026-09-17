<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Http;

use Bitgen\Sdk\Exception\BitgenException;
use JsonException;

/**
 * The HTTP layer of the SDK: URL and query building, headers, JSON bodies, and the mapping of every answer
 * to a decoded value or a BitgenException. The wire itself is the Transport.
 *
 * @internal
 */
final class HttpClient
{
    /** @var array<string, string> */
    private readonly array $headers;

    /**
     * @param string $baseUrl   scheme://host[:port], no trailing slash
     * @param int    $timeoutMs whole request timeout, 0 = none
     */
    public function __construct(
        private readonly Transport $transport,
        public readonly string $scope,
        string $apiKey,
        private readonly string $baseUrl,
        private readonly int $timeoutMs,
        string $userAgent,
    ) {
        $this->headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => $userAgent,
            'BITGEN-Scope' => $scope,
            'Api-key' => $apiKey,
        ];
    }

    /** @param array<string, scalar|null> $query */
    public function get(string $path, array $query = []): mixed
    {
        return $this->request('GET', $path, $query);
    }

    public function post(string $path, mixed $body = null): mixed
    {
        return $this->request('POST', $path, [], $body);
    }

    public function put(string $path, mixed $body = null): mixed
    {
        return $this->request('PUT', $path, [], $body);
    }

    public function patch(string $path, mixed $body = null): mixed
    {
        return $this->request('PATCH', $path, [], $body);
    }

    public function delete(string $path, mixed $body = null): mixed
    {
        return $this->request('DELETE', $path, [], $body);
    }

    /**
     * Sends the request and returns the decoded JSON answer — null on `204` or an empty body.
     * Anything outside 2xx, a 2xx that is not JSON, or no HTTP answer at all → BitgenException.
     *
     * @param non-empty-string           $method
     * @param array<string, scalar|null> $query null entries are skipped, booleans travel as `true` / `false`
     * @param mixed                      $body  JSON-encoded when not null; an empty array is sent as `{}`
     *
     * @throws BitgenException
     */
    public function request(string $method, string $path, array $query = [], mixed $body = null): mixed
    {
        $url = $this->baseUrl . $path . self::queryString($query);
        $encoded = $body === null ? null : self::encode($body);

        try {
            $response = $this->transport->send($method, $url, $this->headers, $encoded, $this->timeoutMs);
        } catch (TransportException $e) {
            throw new BitgenException(0, $e->errorCode, $e);
        }

        $status = $response->status;
        $text = $status === 204 ? '' : $response->body;

        // v4 sends the real HTTP status: anything outside 2xx is an error, a redirect included
        if ($status < 200 || $status > 299) {
            throw new BitgenException($status, self::errorCode($text) ?? self::rawBody($text, $response));
        }
        if (trim($text) === '') {
            return null;
        }
        try {
            return json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            // A 2xx that is not JSON is not an answer of the API (proxy page…): report it as an error
            throw new BitgenException($status, self::rawBody($text, $response));
        }
    }

    /** @param array<string, scalar|null> $query */
    private static function queryString(array $query): string
    {
        $pairs = [];
        foreach ($query as $key => $value) {
            if ($value === null) {
                continue;
            }
            $pairs[$key] = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
        }
        if ($pairs === []) {
            return '';
        }

        return '?' . http_build_query($pairs, '', '&', PHP_QUERY_RFC3986);
    }

    private static function encode(mixed $body): string
    {
        // An empty PHP array would encode as `[]`: the API expects an object
        $value = $body === [] ? new \stdClass() : $body;

        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /** The stable `message` of an API error body `{ error, message, code }`, or null when the body is not one */
    private static function errorCode(string $text): ?string
    {
        try {
            $decoded = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }
        if (!is_array($decoded) || !isset($decoded['message']) || !is_string($decoded['message']) || $decoded['message'] === '') {
            return null;
        }

        return $decoded['message'];
    }

    /** Raw body, or the reason phrase, or the status as text — never empty */
    private static function rawBody(string $text, Response $response): string
    {
        $trimmed = trim($text);
        if ($trimmed !== '') {
            return $trimmed;
        }

        return $response->reason !== '' ? $response->reason : (string) $response->status;
    }
}
