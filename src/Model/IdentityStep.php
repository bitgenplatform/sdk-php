<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** One step of an identity verification (`info`, `selfie`, `identity`, `residency` — `info`, `kbis`, `status`, `domiciliation`, `rbe`) */
final readonly class IdentityStep
{
    public function __construct(
        public string $status,
        public ?int $submittedAt,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(Cast::string($data, 'status'), Cast::nullableInt($data, 'submittedAt'));
    }
}
