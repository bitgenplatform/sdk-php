<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** The type and value of a configuration field — `{ type, value }` */
final readonly class CoreConfigData
{
    public function __construct(
        /** `string`, `int`, `bool`, `password` or `webhook` */
        public string $type,
        /** The value — empty for a secret */
        public mixed $value,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(Cast::string($data, 'type'), Cast::raw($data, 'value'));
    }
}
