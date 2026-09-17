<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** An item of `GET /customer` — carries a `uuid`: it is accepted wherever a customer is expected */
final readonly class Customer
{
    /**
     * @param list<CustomerBusiness> $business the businesses of the customer, each with its KYB file
     * @param list<CustomerAlert>    $alert    active compliance alerts
     */
    public function __construct(
        public string $uuid,
        /** `CustomerState` lists the known values: `CREATED` (activation pending), `ENABLED`, `CLOSED`, `FROZEN` */
        public string $state,
        /** `false` until the customer has activated their account, then `true` */
        public bool $isAvailable,
        public int $createdAt,
        /** The email */
        public string $login,
        /** Whether the customer may sign in to the BITGEN web application */
        public bool $canLogin,
        public CustomerAccount $account,
        public CustomerClient $client,
        public CustomerAction $action,
        public Identity $identity,
        public array $business,
        public CustomerCollaborations $collaborations,
        public array $alert,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            Cast::string($data, 'uuid'),
            Cast::string($data, 'state'),
            Cast::bool($data, 'isAvailable'),
            Cast::int($data, 'createdAt'),
            Cast::string($data, 'login'),
            Cast::bool($data, 'canLogin'),
            CustomerAccount::fromArray(Cast::object($data, 'account')),
            CustomerClient::fromArray(Cast::object($data, 'client')),
            CustomerAction::fromArray(Cast::object($data, 'action')),
            Identity::fromArray(Cast::object($data, 'identity')),
            Cast::objects($data, 'business', CustomerBusiness::fromArray(...)),
            CustomerCollaborations::fromArray(Cast::object($data, 'collaborations')),
            Cast::objects($data, 'alert', CustomerAlert::fromArray(...)),
        );
    }
}
