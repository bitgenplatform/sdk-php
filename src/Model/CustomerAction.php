<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** `Customer::$action` — carries the settings (`setup`) */
final readonly class CustomerAction
{
    public function __construct(
        public CustomerSetup $setup,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(CustomerSetup::fromArray(Cast::object($data, 'setup')));
    }
}
