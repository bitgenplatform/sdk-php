<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/**
 * The EUR value curve of the whole custody of a customer (`GET /custody/{user}/portfolio`): `{ uuid, type, history }`,
 * or a flat `{ history }` at zero — `uuid` and `type` null — while the customer has no custody yet.
 */
final readonly class CustodyPortfolio
{
    public function __construct(
        /** The custody account of the customer — null while they have no custody */
        public ?string $uuid,
        /** `WalletType` lists the known values: `USER`, `TREASURY` — null while the customer has no custody */
        public ?string $type,
        /** EUR value of the custody */
        public History $history,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            Cast::nullableString($data, 'uuid'),
            Cast::nullableString($data, 'type'),
            History::fromArray(Cast::object($data, 'history')),
        );
    }
}
