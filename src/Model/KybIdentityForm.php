<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** The KYB questionnaire: the business `activity`, plus the internal `score` */
final readonly class KybIdentityForm
{
    public function __construct(
        public ?string $activity,
        public ?int $submittedAt,
        /** Internal scoring */
        public int $score,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(Cast::nullableString($data, 'activity'), Cast::nullableInt($data, 'submittedAt'), Cast::int($data, 'score'));
    }
}
