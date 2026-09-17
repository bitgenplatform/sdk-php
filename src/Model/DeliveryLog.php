<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** One delivery attempt of a subscription (`GET /webhooks/{subscriber}/logs`) — property names as the API gives them */
final readonly class DeliveryLog
{
    /**
     * @param array<string, mixed> $payload the delivered body
     */
    public function __construct(
        /** Epoch seconds */
        public int $date,
        /** The event name */
        public string $webhook,
        /** The endpoint called */
        public string $url,
        /** `SENT` or `FAILED` for that attempt */
        public string $status,
        /** The status the endpoint answered */
        public ?int $http_code,
        public ?int $duration_ms,
        /** Attempt number */
        public int $attempts,
        public array $payload,
        /** Failure reason, null on success */
        public ?string $error,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            Cast::int($data, 'date'),
            Cast::string($data, 'webhook'),
            Cast::string($data, 'url'),
            Cast::string($data, 'status'),
            Cast::nullableInt($data, 'http_code'),
            Cast::nullableInt($data, 'duration_ms'),
            Cast::int($data, 'attempts'),
            Cast::object($data, 'payload'),
            Cast::nullableString($data, 'error'),
        );
    }
}
