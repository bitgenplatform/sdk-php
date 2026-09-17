<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** One field of a connector's configuration schema — the secrets live in the organization's configuration, never here */
final readonly class CoreConfigField
{
    /**
     * @param array<string, string> $label display names by language (`fr`, `en`)
     */
    public function __construct(
        /** The key of the field (`min_deposit`, `apr`…) */
        public string $name,
        public array $label,
        public CoreConfigData $data,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(Cast::string($data, 'name'), Cast::stringMap($data, 'label'), CoreConfigData::fromArray(Cast::object($data, 'data')));
    }
}
