<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/**
 * The verification file of a person (`mode` `KYC` → `KycIdentity`) or of a business (`KYB` → `KybIdentity`).
 * `fromArray` builds the subclass the `mode` names; a mode the contract does not list yet gives this base
 * class, with the common fields only.
 */
readonly class Identity
{
    public function __construct(
        public string $uuid,
        /** `IdentityState` lists the known values: `CREATED`, `IN_PROGRESS`, `WAIT`, `PENDING`, `VALIDATED`, `REJECTED`, `FROZEN`, `EXPIRED`, `CLOSED` */
        public string $state,
        /** `KYC` or `KYB` (`IdentityMode`) */
        public string $mode,
        public IdentityData $data,
        public ?int $validatedAt,
        public ?int $expiresAt,
        /** When the renewal reminder was sent — an identity expires after 12 months (6 for a politically exposed person) */
        public ?int $renewalNotifiedAt,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return match (Cast::string($data, 'mode')) {
            IdentityMode::KYC => KycIdentity::fromKyc($data),
            IdentityMode::KYB => KybIdentity::fromKyb($data),
            default => new self(...self::commonFields($data)),
        };
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array{string, string, string, IdentityData, ?int, ?int, ?int}
     *
     * @internal
     */
    protected static function commonFields(array $data): array
    {
        return [
            Cast::string($data, 'uuid'),
            Cast::string($data, 'state'),
            Cast::string($data, 'mode'),
            IdentityData::fromArray(Cast::object($data, 'data')),
            Cast::nullableInt($data, 'validatedAt'),
            Cast::nullableInt($data, 'expiresAt'),
            Cast::nullableInt($data, 'renewalNotifiedAt'),
        ];
    }
}
