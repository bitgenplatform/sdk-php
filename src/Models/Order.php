<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Models;

readonly class Order
{
    public function __construct(
        public int        $createdAt,
        public string     $state,
        public string     $owner,
        public OrderData  $data,
        public string|float $amount,
        public string     $currency,
        public ?int       $deliveredAt,
        public ?string    $error,
        public ?string    $tunnel,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            createdAt:   (int)    $data['createdAt'],
            state:       (string) $data['state'],
            owner:       (string) $data['owner'],
            data:        OrderData::fromArray($data['data']),
            amount:      $data['amount'],
            currency:    (string) $data['currency'],
            deliveredAt: isset($data['deliveredAt']) ? (int) $data['deliveredAt'] : null,
            error:       $data['error'] ?? null,
            tunnel:      isset($data['tunnel']) ? (string) $data['tunnel'] : null,
        );
    }
}
