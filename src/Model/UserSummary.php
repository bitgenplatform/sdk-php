<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/**
 * A user as a transaction or a staking movement references them — `{ uuid, state, login, account: { firstname, lastname, fin } }`:
 * the customer (`owner`) or the compliance officer in charge (`assignee`). An `owner` carries the customer's `uuid`: it is accepted
 * wherever a customer is expected.
 */
final readonly class UserSummary
{
    public function __construct(
        public string $uuid,
        /** `CustomerState` lists the known values */
        public string $state,
        /** The email */
        public string $login,
        public UserSummaryAccount $account,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            Cast::string($data, 'uuid'),
            Cast::string($data, 'state'),
            Cast::string($data, 'login'),
            UserSummaryAccount::fromArray(Cast::object($data, 'account')),
        );
    }
}
