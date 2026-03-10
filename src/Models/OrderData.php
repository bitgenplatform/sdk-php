<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Models;

readonly class OrderData
{
    public function __construct(
        public string  $label,
        public string  $asset,
        public string  $mode,
        public ?float  $deliveredAmount,
        public ?float  $deliveredPrice,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            label:           (string) $data['label'],
            asset:           (string) $data['asset'],
            mode:            (string) $data['mode'],
            deliveredAmount: isset($data['deliveredAmount']) ? (float) $data['deliveredAmount'] : null,
            deliveredPrice:  isset($data['deliveredPrice'])  ? (float) $data['deliveredPrice']  : null,
        );
    }
}
