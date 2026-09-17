<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** A business of a customer, with its KYB file */
final readonly class CustomerBusiness
{
    public function __construct(
        public Identity $identity,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(Identity::fromArray(Cast::object($data, 'identity')));
    }
}
