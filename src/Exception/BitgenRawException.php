<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Exception;

use RuntimeException;

/**
 * Thrown exclusively by TransactionResource::create() when the server returns
 * HTTP 500 (invalid_asset) or HTTP 400 (missing required field) as plain text
 * instead of a standard JSON error body.
 */
class BitgenRawException extends RuntimeException
{
    public function __construct(
        public readonly int    $status,
        public readonly string $rawBody,
    ) {
        parent::__construct(sprintf('HTTP %d: %s', $status, $rawBody), $status);
    }
}
