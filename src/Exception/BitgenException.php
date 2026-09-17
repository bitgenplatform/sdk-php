<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Exception;

use RuntimeException;
use Throwable;

/**
 * Error answered by the API — or no HTTP answer at all.
 *
 * `$status` is the HTTP status (`0` when no HTTP response was received: `request_timeout`, `network_error`),
 * `$errorCode` the stable code of the API (`invalid_amount`, `unknown_asset`…) — or the raw response text,
 * truncated to 200 characters, when the body is not the API's JSON error.
 * `getMessage()` is `"<errorCode> (HTTP <status>)"`, `getCode()` is the status.
 * Nothing in it ever contains the API key.
 */
class BitgenException extends RuntimeException
{
    /** Longest `errorCode` kept: the API sends short snake_case codes, anything longer is a foreign body (proxy page…) */
    public const MAX_CODE_LENGTH = 200;

    public readonly string $errorCode;

    public function __construct(
        public readonly int $status,
        string $errorCode,
        ?Throwable $previous = null,
    ) {
        $this->errorCode = self::truncate($errorCode);
        parent::__construct(sprintf('%s (HTTP %d)', $this->errorCode, $status), $status, $previous);
    }

    /** First 200 characters (UTF-8 aware, falls back to bytes on invalid UTF-8) */
    private static function truncate(string $text): string
    {
        if (preg_match('/^.{0,' . self::MAX_CODE_LENGTH . '}/us', $text, $match) === 1) {
            return $match[0];
        }

        return substr($text, 0, self::MAX_CODE_LENGTH);
    }
}
