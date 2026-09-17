<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** Email preferences of a customer: login-related emails, BITGEN newsletter */
final readonly class AccountNotifications
{
    public function __construct(
        public bool $login,
        public bool $newsletter,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(Cast::bool($data, 'login'), Cast::bool($data, 'newsletter'));
    }
}
