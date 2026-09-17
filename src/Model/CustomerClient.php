<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** Platform-side flags of a customer's account */
final readonly class CustomerClient
{
    /**
     * @param list<string> $roles platform roles — always `ROLE_USER` for a customer
     */
    public function __construct(
        public array $roles,
        /** Two-factor authentication enabled */
        public bool $hasTfa,
        /** Anti-phishing code enabled */
        public bool $hasPhishing,
        /** `true` once the account has been activated */
        public bool $isValid,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(Cast::strings($data, 'roles'), Cast::bool($data, 'hasTfa'), Cast::bool($data, 'hasPhishing'), Cast::bool($data, 'isValid'));
    }
}
