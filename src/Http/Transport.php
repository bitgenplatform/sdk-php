<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Http;

/**
 * Sends one HTTP request and returns the raw response. Never follows redirects.
 *
 * @internal
 */
interface Transport
{
    /**
     * @param non-empty-string      $method  HTTP verb
     * @param array<string, string> $headers header name → value
     * @param string|null           $body    request body, already serialized — null sends no body
     * @param int                   $timeoutMs whole request timeout in milliseconds, 0 = none
     *
     * @throws TransportException when no HTTP response was received (timeout, DNS, connection, TLS…)
     */
    public function send(string $method, string $url, array $headers, ?string $body, int $timeoutMs): Response;
}
