<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Support;

use Bitgen\Sdk\Model\Account;
use Bitgen\Sdk\Model\Created;
use Bitgen\Sdk\Model\Customer;
use Bitgen\Sdk\Model\OrderUser;
use Bitgen\Sdk\Model\UserSummary;
use InvalidArgumentException;

/**
 * A customer, as accepted by every method expecting one: their uuid (or an email, where the API resolves it),
 * or one of the models that carry a customer's uuid — the `Created` of `customer->create()`, a `Customer`,
 * an `Account`, the `user` of an `Order`, the `owner` of a `Transaction` or of a `StakingMovement`.
 *
 * @internal
 *
 * @phpstan-type UserRef string|Created|Customer|Account|UserSummary|OrderUser
 */
final class UserId
{
    /**
     * @throws InvalidArgumentException empty string, or a model without a non-empty `uuid`
     */
    public static function resolve(string|Created|Customer|Account|UserSummary|OrderUser $user): string
    {
        $value = is_string($user) ? $user : $user->uuid;
        if (trim($value) === '') {
            throw new InvalidArgumentException('user must be a non-empty uuid or email, or a model with a non-empty uuid');
        }

        return $value;
    }
}
