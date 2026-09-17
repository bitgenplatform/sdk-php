<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/**
 * States of a webhook subscription (`Subscriber::$state`) and of an event of the catalogue (`WebhookType::$state`) —
 * strings; `WebhooksResource::list()` leaves the `ARCHIVED` subscriptions out unless `$includeArchived`
 */
final class SubscriberState
{
    public const ENABLED = 'ENABLED';
    public const ARCHIVED = 'ARCHIVED';

    /** Every value, in the order of the contract */
    public const VALUES = [self::ENABLED, self::ARCHIVED];

    private function __construct()
    {
    }
}
