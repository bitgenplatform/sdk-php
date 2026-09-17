<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/**
 * Travel rule information on the destination of an on-chain withdrawal: a person (`TravelRulePerson`) or a
 * platform (`TravelRulePlatform`) — one form or the other, 255 characters max per field (checked by the API).
 */
abstract readonly class TravelRule
{
    /**
     * The `travelRule` object of the request body
     *
     * @internal
     *
     * @return array<string, string>
     */
    abstract public function toArray(): array;
}
