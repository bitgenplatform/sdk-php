<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** An active compliance alert on a customer */
final readonly class CustomerAlert
{
    /**
     * @param array<string, mixed> $sources the observations behind the alert (analysis data)
     */
    public function __construct(
        public string $uuid,
        /** `OPEN`, `DECLARATED`, `CONFIRMED` */
        public string $state,
        /** `SUCCESS`, `WARNING`, `CRITICAL` */
        public string $severity,
        public array $sources,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(Cast::string($data, 'uuid'), Cast::string($data, 'state'), Cast::string($data, 'severity'), Cast::object($data, 'sources'));
    }
}
