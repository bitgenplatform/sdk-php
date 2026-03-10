<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Models;

readonly class UserIdentity
{
    public function __construct(
        public string  $state,
        public bool    $isVerified,
        public ?string $verificationUrl,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            state:           (string) $data['state'],
            isVerified:      (bool)   $data['isVerified'],
            verificationUrl: $data['verificationUrl'] ?? null,
        );
    }
}
