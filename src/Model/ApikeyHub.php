<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** The hub the organization of a key belongs to — `{ uuid, state, name, options }` */
final readonly class ApikeyHub
{
    /**
     * @param array<string, mixed> $options internal
     */
    public function __construct(
        public string $uuid,
        public string $state,
        public string $name,
        public array $options,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(Cast::string($data, 'uuid'), Cast::string($data, 'state'), Cast::string($data, 'name'), Cast::object($data, 'options'));
    }
}
