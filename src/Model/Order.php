<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** A trading order: a purchase or a sale of crypto for a customer (`GET /trading/{order}`, `GET /trading/orders`) */
final readonly class Order
{
    public function __construct(
        /** The order — the `tunnel` returned by `buy` / `sell` */
        public string $uuid,
        /** `OrderState` lists the known values: `REGISTERED`, `TRANSFERRING`, `DEPOSITED`, `EXECUTING`, `FILLED`, `DELIVERING`, `DONE`, `PARKED`, `FAILED` */
        public string $state,
        /** `BUY` or `SELL` (`OrderSide`) */
        public string $side,
        /** What was asked, as a string: EUR for a purchase (`"25.00"`), a crypto quantity for a sale */
        public string $amount,
        /** The idempotency key given, or null */
        public ?string $reference,
        /** What the customer got, or null: the crypto quantity for a purchase, the EUR credited for a sale */
        public ?float $received,
        /** The EUR price of the token, or null */
        public ?float $executedPrice,
        /** Exchange fee, in EUR, or null */
        public ?float $fee,
        /** Epoch seconds — null until the order completes */
        public ?int $completedAt,
        /** Epoch seconds */
        public int $createdAt,
        public OrderUser $user,
        public OrderOrganization $organization,
        public AssetRef $asset,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            Cast::string($data, 'uuid'),
            Cast::string($data, 'state'),
            Cast::string($data, 'side'),
            Cast::string($data, 'amount'),
            Cast::nullableString($data, 'reference'),
            Cast::nullableFloat($data, 'received'),
            Cast::nullableFloat($data, 'executedPrice'),
            Cast::nullableFloat($data, 'fee'),
            Cast::nullableInt($data, 'completedAt'),
            Cast::int($data, 'createdAt'),
            OrderUser::fromArray(Cast::object($data, 'user')),
            OrderOrganization::fromArray(Cast::object($data, 'organization')),
            AssetRef::fromArray(Cast::object($data, 'asset')),
        );
    }
}
