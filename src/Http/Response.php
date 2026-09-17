<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Http;

/**
 * What a Transport got back: the status, the reason phrase of the status line (may be empty, HTTP/2 has none) and the raw body.
 *
 * @internal
 */
final readonly class Response
{
    public function __construct(
        public int $status,
        public string $reason,
        public string $body,
    ) {
    }
}
