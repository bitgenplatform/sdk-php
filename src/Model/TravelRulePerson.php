<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

use InvalidArgumentException;

/** The destination of a withdrawal is a person — `{ firstname?, lastname?, address? }`, at least one of them */
final readonly class TravelRulePerson extends TravelRule
{
    /**
     * @throws InvalidArgumentException none of the fields is given
     */
    public function __construct(
        public ?string $firstname = null,
        public ?string $lastname = null,
        public ?string $address = null,
    ) {
        if ($firstname === null && $lastname === null && $address === null) {
            throw new InvalidArgumentException('a TravelRulePerson needs at least one of firstname, lastname or address');
        }
    }

    public function toArray(): array
    {
        return array_filter(['firstname' => $this->firstname, 'lastname' => $this->lastname, 'address' => $this->address], static fn (?string $value): bool => $value !== null);
    }
}
