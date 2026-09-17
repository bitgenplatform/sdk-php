<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Http;

use RuntimeException;

/**
 * No HTTP response at all. `$errorCode` is `request_timeout` or `network_error`; the message is the
 * transport's own error (it never contains the API key, which only travels in headers).
 *
 * @internal
 */
final class TransportException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        int $code = 0,
    ) {
        parent::__construct($message, $code);
    }
}
