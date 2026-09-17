<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** `GET /account/{user}` — one customer's account: identity files, details, settings. Carries a `uuid`: accepted wherever a customer is expected. */
final readonly class Account
{
    /**
     * @param list<CustomerBusiness> $business the businesses of the customer, each with its KYB file
     */
    public function __construct(
        public string $uuid,
        public Identity $identity,
        public array $business,
        /** The same fields as `Customer::$account`, with `address` reduced to `{ uuid, address }` */
        public CustomerAccount $account,
        public AccountNotifications $notifications,
        public CustomerSetup $setup,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            Cast::string($data, 'uuid'),
            Identity::fromArray(Cast::object($data, 'identity')),
            Cast::objects($data, 'business', CustomerBusiness::fromArray(...)),
            CustomerAccount::fromArray(Cast::object($data, 'account')),
            AccountNotifications::fromArray(Cast::object($data, 'notifications')),
            CustomerSetup::fromArray(Cast::object($data, 'setup')),
        );
    }
}
