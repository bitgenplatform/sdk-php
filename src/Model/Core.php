<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/**
 * A connector of the platform: a bank (`RAMP`), an exchange (`TRADING`), a custodian (`CUSTODY`), a staking provider
 * (`STAKING`), an identity (`IDENTITY`) or anti-money-laundering (`AML`) service (`GET /applications/core`).
 */
final readonly class Core
{
    /**
     * @param list<CoreConfigField> $config the configuration schema of the connector
     */
    public function __construct(
        public string $uuid,
        /** `ENABLED` or `DISABLED` — `CoreState` */
        public string $state,
        /** The identifier of the connector — for a `STAKING` connector, `<provider>_<iso>` (`figment_sol`, `bitgen_eth`) */
        public string $name,
        /** Display name */
        public string $label,
        /** `CoreType` lists the known values: `IDENTITY`, `AML`, `TRADING`, `CUSTODY`, `STAKING`, `RAMP` */
        public string $type,
        /** The asset of a `STAKING` connector (derived from its `name`) — null for the other types */
        public ?AssetRef $asset,
        public array $config,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $asset = Cast::nullableObject($data, 'asset');

        return new self(
            Cast::string($data, 'uuid'),
            Cast::string($data, 'state'),
            Cast::string($data, 'name'),
            Cast::string($data, 'label'),
            Cast::string($data, 'type'),
            $asset === null ? null : AssetRef::fromArray($asset),
            Cast::objects($data, 'config', CoreConfigField::fromArray(...)),
        );
    }
}
