<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** The compliance alert attached to a transaction — analysis data, kept as the API gives it beyond the identified fields */
final readonly class TransactionAlert
{
    /**
     * @param mixed                $factors      the elements that weighed in the analysis
     * @param array<string, mixed> $sources      the observations analysed
     * @param mixed                $history      the state changes of the alert
     * @param mixed                $user         the customer
     * @param mixed                $assignee     the compliance officer
     * @param mixed                $organization the organization
     */
    public function __construct(
        public string $uuid,
        /** `OPEN`, `RESOLVED`, `DISMISSED`, `DECLARATED`, `CONFIRMED` */
        public string $state,
        /** `SUCCESS`, `WARNING`, `CRITICAL` */
        public string $severity,
        /** `KYT`, `KYC_EXPIRE`, `SUSPICIOUS_ACTIVITY`, `AML`, `SANCTIONS` */
        public string $type,
        public string $description,
        /** Confidence of the analysis, 0–100 */
        public float $confidence,
        /** Suggested action */
        public ?string $recommendation,
        public mixed $factors,
        public array $sources,
        public mixed $history,
        /** Groups the alerts of a same incident */
        public ?string $incidentKey,
        public int $createdAt,
        public int $updatedAt,
        public mixed $user,
        public mixed $assignee,
        public mixed $organization,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            Cast::string($data, 'uuid'),
            Cast::string($data, 'state'),
            Cast::string($data, 'severity'),
            Cast::string($data, 'type'),
            Cast::string($data, 'description'),
            Cast::float($data, 'confidence'),
            Cast::nullableString($data, 'recommendation'),
            Cast::raw($data, 'factors'),
            Cast::object($data, 'sources'),
            Cast::raw($data, 'history'),
            Cast::nullableString($data, 'incidentKey'),
            Cast::int($data, 'createdAt'),
            Cast::int($data, 'updatedAt'),
            Cast::raw($data, 'user'),
            Cast::raw($data, 'assignee'),
            Cast::raw($data, 'organization'),
        );
    }
}
