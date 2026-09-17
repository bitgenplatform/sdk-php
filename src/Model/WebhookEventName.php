<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/**
 * The events of the catalogue the contract lists — `WebhookEvent::$event` and `WebhookType::$name` are strings
 * (the catalogue may grow); `subscribe()` takes a constant, any name or uuid of the catalogue, or a `WebhookType`.
 */
final class WebhookEventName
{
    public const USER_CREATED = 'user.created';
    public const ORGANIZATION_CREATED = 'organization.created';
    public const USER_IDENTITY_STARTED = 'user.identity.started';
    public const USER_IDENTITY_PENDING = 'user.identity.pending';
    public const USER_IDENTITY_STEP_VALIDATED = 'user.identity.step.validated';
    public const USER_IDENTITY_STEP_REJECTED = 'user.identity.step.rejected';
    public const USER_IDENTITY_STEP_REQUESTED = 'user.identity.step.requested';
    public const USER_IDENTITY_VALIDATED = 'user.identity.validated';
    public const USER_IDENTITY_RENEW = 'user.identity.renew';
    public const USER_IDENTITY_REQUEST = 'user.identity.request';
    public const ORGANIZATION_IDENTITY_STARTED = 'organization.identity.started';
    public const ORGANIZATION_IDENTITY_PENDING = 'organization.identity.pending';
    public const ORGANIZATION_IDENTITY_STEP_VALIDATED = 'organization.identity.step.validated';
    public const ORGANIZATION_IDENTITY_STEP_REJECTED = 'organization.identity.step.rejected';
    public const ORGANIZATION_IDENTITY_STEP_REQUESTED = 'organization.identity.step.requested';
    public const ORGANIZATION_IDENTITY_VALIDATED = 'organization.identity.validated';
    public const ORGANIZATION_IDENTITY_RENEW = 'organization.identity.renew';
    public const ORGANIZATION_IDENTITY_REQUEST = 'organization.identity.request';
    public const BANK_CREDITED = 'bank.credited';
    public const BANK_DEBITED = 'bank.debited';
    public const BANK_TRANSACTION = 'bank.transaction';
    public const CUSTODY_TRANSACTION = 'custody.transaction';
    public const CUSTODY_WALLET_CREATED = 'custody.wallet.created';
    public const CUSTODY_SENT = 'custody.sent';
    public const CUSTODY_RECEIVED = 'custody.received';
    public const TRADING_BUY = 'trading.buy';
    public const TRADING_SELL = 'trading.sell';
    public const STAKING_REQUESTED = 'staking.requested';
    public const STAKING_STATUS = 'staking.status';
    public const STAKING_REWARDS = 'staking.rewards';
    public const STAKING_CLAIMED = 'staking.claimed';
    public const ALERT_OPENED = 'alert.opened';
    public const ALERT_STATUS = 'alert.status';

    /** Every value, in the order of the contract */
    public const VALUES = [self::USER_CREATED, self::ORGANIZATION_CREATED, self::USER_IDENTITY_STARTED, self::USER_IDENTITY_PENDING, self::USER_IDENTITY_STEP_VALIDATED, self::USER_IDENTITY_STEP_REJECTED, self::USER_IDENTITY_STEP_REQUESTED, self::USER_IDENTITY_VALIDATED, self::USER_IDENTITY_RENEW, self::USER_IDENTITY_REQUEST, self::ORGANIZATION_IDENTITY_STARTED, self::ORGANIZATION_IDENTITY_PENDING, self::ORGANIZATION_IDENTITY_STEP_VALIDATED, self::ORGANIZATION_IDENTITY_STEP_REJECTED, self::ORGANIZATION_IDENTITY_STEP_REQUESTED, self::ORGANIZATION_IDENTITY_VALIDATED, self::ORGANIZATION_IDENTITY_RENEW, self::ORGANIZATION_IDENTITY_REQUEST, self::BANK_CREDITED, self::BANK_DEBITED, self::BANK_TRANSACTION, self::CUSTODY_TRANSACTION, self::CUSTODY_WALLET_CREATED, self::CUSTODY_SENT, self::CUSTODY_RECEIVED, self::TRADING_BUY, self::TRADING_SELL, self::STAKING_REQUESTED, self::STAKING_STATUS, self::STAKING_REWARDS, self::STAKING_CLAIMED, self::ALERT_OPENED, self::ALERT_STATUS];

    private function __construct()
    {
    }
}
