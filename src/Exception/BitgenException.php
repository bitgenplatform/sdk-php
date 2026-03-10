<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Exception;

use RuntimeException;

/**
 * Note: the property is named $errorCode (not $code) to avoid a conflict
 * with the non-readonly $code property inherited from PHP's base Exception class.
 */
class BitgenException extends RuntimeException
{
    public readonly int    $status;
    public readonly int    $errorCode;
    public readonly string $service;
    public readonly string $module;
    public readonly string $apiMessage;

    public function __construct(
        int    $status,
        int    $errorCode,
        string $service,
        string $module,
        string $apiMessage,
    ) {
        $this->status     = $status;
        $this->errorCode  = $errorCode;
        $this->service    = $service;
        $this->module     = $module;
        $this->apiMessage = $apiMessage;

        parent::__construct(
            sprintf('[%s/%s] %s (HTTP %d, code %d)', $module, $service, $apiMessage, $status, $errorCode),
            $errorCode,
        );
    }

    public static function fromArray(int $httpStatus, array $error): self
    {
        return new self(
            status:     $httpStatus,
            errorCode:  (int)    ($error['code']    ?? 0),
            service:    (string) ($error['service'] ?? ''),
            module:     (string) ($error['module']  ?? ''),
            apiMessage: (string) ($error['message'] ?? ''),
        );
    }
}
