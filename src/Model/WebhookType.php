<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** An event of the catalogue (`GET /webhook`) */
final readonly class WebhookType
{
    public function __construct(
        public string $uuid,
        /** `ENABLED` or `ARCHIVED` */
        public string $state,
        /** The event name (`custody.sent`) — `WebhookEventName` lists the known ones */
        public string $name,
        /** Display names, raw JSON `{"fr": "…", "en": "…"}` */
        public string $label,
        /** Internal, raw JSON */
        public string $data,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            Cast::string($data, 'uuid'),
            Cast::string($data, 'state'),
            Cast::string($data, 'name'),
            Cast::string($data, 'label'),
            Cast::string($data, 'data'),
        );
    }
}
