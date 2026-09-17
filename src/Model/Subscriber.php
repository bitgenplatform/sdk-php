<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** A subscription of the organization to an event of the catalogue */
final readonly class Subscriber
{
    public function __construct(
        public string $uuid,
        /** `SubscriberState` lists the known values: `ENABLED`, `ARCHIVED` */
        public string $state,
        public int $updatedAt,
        /** The event of the catalogue */
        public WebhookType $webhook,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            Cast::string($data, 'uuid'),
            Cast::string($data, 'state'),
            Cast::int($data, 'updatedAt'),
            WebhookType::fromArray(Cast::object($data, 'webhook')),
        );
    }
}
