<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** The answers of the KYC questionnaire (property names as in the contract), plus the internal `score` */
final readonly class KycIdentityForm
{
    public function __construct(
        public mixed $european_residency,
        /** Politically exposed person */
        public mixed $ppe,
        /** Relative of a politically exposed person */
        public mixed $ppp,
        public ?string $source_income,
        public ?string $net_income,
        /** Crypto experience */
        public ?string $experience,
        public ?int $submittedAt,
        /** Internal scoring */
        public int $score,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            Cast::raw($data, 'european_residency'),
            Cast::raw($data, 'ppe'),
            Cast::raw($data, 'ppp'),
            Cast::nullableString($data, 'source_income'),
            Cast::nullableString($data, 'net_income'),
            Cast::nullableString($data, 'experience'),
            Cast::nullableInt($data, 'submittedAt'),
            Cast::int($data, 'score'),
        );
    }
}
