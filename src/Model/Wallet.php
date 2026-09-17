<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/**
 * A custody wallet: the crypto of a customer (or of the organization) for one asset, at the custodian
 * (`GET /custody/{user}`, `GET /custody/{user}/{asset}`).
 */
final readonly class Wallet
{
    public function __construct(
        public string $uuid,
        /** `WalletState` lists the known values: `CREATED`, `FROZEN` */
        public string $state,
        /** `WalletType` lists the known values: `USER` (a customer), `TREASURY` (the organization, read-only) */
        public string $type,
        /** Deposit address */
        public ?string $address,
        /** The same deposit address in the legacy format of the chain, when the network has two */
        public ?string $addressLegacy,
        /** Memo / tag of the address (XRP, XLM…) */
        public ?string $tag,
        /** Exact quantity, as a string */
        public string $balance,
        /** EUR value curve — only on the unit read (`$client->custody->wallet()`): null in the list, and until it has been computed for a new wallet */
        public ?History $history,
        public AssetRef $asset,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $history = Cast::object($data, 'history');

        return new self(
            Cast::string($data, 'uuid'),
            Cast::string($data, 'state'),
            Cast::string($data, 'type'),
            Cast::nullableString($data, 'address'),
            Cast::nullableString($data, 'addressLegacy'),
            Cast::nullableString($data, 'tag'),
            Cast::string($data, 'balance'),
            $history === [] ? null : History::fromArray($history),
            AssetRef::fromArray(Cast::object($data, 'asset')),
        );
    }
}
