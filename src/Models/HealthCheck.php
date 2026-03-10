<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Models;

readonly class HealthCheck
{
    public function __construct(
        public string $name,
        public string $version,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name:    (string) $data['name'],
            version: (string) $data['version'],
        );
    }
}
