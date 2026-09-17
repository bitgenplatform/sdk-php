<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** The envelope of a delivery received by the endpoint, returned by `verify()` — property names as the API sends them */
final readonly class WebhookEvent
{
    public function __construct(
        /** The delivery */
        public string $delivery_id,
        /** The time of the delivery, epoch seconds */
        public int $timestamp,
        /** The event name — `WebhookEventName` lists the known ones */
        public string $event,
        /** The payload of the event — its shape depends on the event */
        public mixed $data,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            Cast::string($data, 'delivery_id'),
            Cast::int($data, 'timestamp'),
            Cast::string($data, 'event'),
            Cast::raw($data, 'data'),
        );
    }
}
