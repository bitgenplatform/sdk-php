<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** `GET /webhooks/{organization}` — the HMAC secret, the endpoint and the subscriptions of the organization */
final readonly class WebhookSubscriptions
{
    /**
     * @param list<Subscriber> $items
     */
    public function __construct(
        /** The secret the deliveries are signed with — keep it server-side, for `verify()` */
        public string $secret,
        /** The URL the events are delivered to */
        public string $endpoint,
        public array $items,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            Cast::string($data, 'secret'),
            Cast::string($data, 'endpoint'),
            Cast::objects($data, 'items', Subscriber::fromArray(...)),
        );
    }
}
