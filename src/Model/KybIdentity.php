<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** The KYB file of a business: the common identity fields plus the questionnaire (`form`) */
final readonly class KybIdentity extends Identity
{
    public function __construct(
        string $uuid,
        string $state,
        string $mode,
        IdentityData $data,
        ?int $validatedAt,
        ?int $expiresAt,
        ?int $renewalNotifiedAt,
        public KybIdentityForm $form,
    ) {
        parent::__construct($uuid, $state, $mode, $data, $validatedAt, $expiresAt, $renewalNotifiedAt);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @internal use `Identity::fromArray`
     */
    public static function fromKyb(array $data): self
    {
        return new self(...self::commonFields($data), form: KybIdentityForm::fromArray(Cast::object($data, 'form')));
    }
}
