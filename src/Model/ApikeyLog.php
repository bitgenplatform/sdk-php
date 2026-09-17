<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** One call made with a key (`GET /organization/{organization}/apikeys/{apikey}/logs`) */
final readonly class ApikeyLog
{
    public function __construct(
        /** Epoch seconds */
        public int $date,
        /** `"GET /custody/…"` */
        public string $path,
        /** The inputs of the call, a JSON string — personal data masked */
        public string $payload,
        /** The HTTP status answered */
        public int $status,
        /** The response body of a failed call, null when it succeeded */
        public ?string $error,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            Cast::int($data, 'date'),
            Cast::string($data, 'path'),
            Cast::string($data, 'payload'),
            Cast::int($data, 'status'),
            Cast::nullableString($data, 'error'),
        );
    }
}
