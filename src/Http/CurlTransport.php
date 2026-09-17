<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Http;

use CurlHandle;

/**
 * The transport of the SDK: ext-curl, TLS verified, redirects never followed.
 *
 * @internal
 */
final class CurlTransport implements Transport
{
    public function send(string $method, string $url, array $headers, ?string $body, int $timeoutMs): Response
    {
        $reason = '';
        $lines = [];
        foreach ($headers as $name => $value) {
            $lines[] = $name . ': ' . $value;
        }
        // curl would add `Expect: 100-continue` on larger bodies, waiting for the server: not with an API that answers JSON right away
        $lines[] = 'Expect:';

        $handle = curl_init($url);
        if ($handle === false) {
            throw new TransportException('network_error', 'curl_init failed');
        }

        $options = [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $lines,
            // A redirect is reported as is: the key must never be replayed to another host
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            // No signals for timeouts: safe in threaded SAPIs, the resolver is threaded anyway
            CURLOPT_NOSIGNAL => true,
            CURLOPT_HEADERFUNCTION => static function (CurlHandle $handle, string $line) use (&$reason): int {
                if (preg_match('#^HTTP/\S+\s+\d{3}\s*(.*?)\s*$#', $line, $match) === 1) {
                    $reason = $match[1];
                }

                return strlen($line);
            },
        ];
        if ($body !== null) {
            $options[CURLOPT_POSTFIELDS] = $body;
        } elseif ($method !== 'GET' && $method !== 'HEAD') {
            // A body-less POST / PUT / PATCH / DELETE still announces `Content-Length: 0`, as fetch does
            $options[CURLOPT_POSTFIELDS] = '';
        }
        if ($timeoutMs > 0) {
            $options[CURLOPT_TIMEOUT_MS] = $timeoutMs;
        }
        curl_setopt_array($handle, $options);

        $out = curl_exec($handle);
        if (!is_string($out)) {
            $errno = curl_errno($handle);
            $error = curl_error($handle);

            throw new TransportException($errno === CURLE_OPERATION_TIMEDOUT ? 'request_timeout' : 'network_error', $error, $errno);
        }
        /** @var int $status */
        $status = curl_getinfo($handle, CURLINFO_RESPONSE_CODE);

        return new Response($status, $reason, $out);
    }
}
