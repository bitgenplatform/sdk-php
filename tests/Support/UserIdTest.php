<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Tests\Support;

use Bitgen\Sdk\Model\Account;
use Bitgen\Sdk\Model\Created;
use Bitgen\Sdk\Model\Customer;
use Bitgen\Sdk\Model\OrderUser;
use Bitgen\Sdk\Model\UserSummary;
use Bitgen\Sdk\Support\UserId;
use Bitgen\Sdk\Tests\Resource\CustomerResourceTest;
use Bitgen\Sdk\Tests\Resource\TransactionResourceTest;
use Bitgen\Sdk\Tests\TypeErrors;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class UserIdTest extends TestCase
{
    use TypeErrors;

    public function testUuidEmailAndTheModelsThatCarryACustomerUuid(): void
    {
        self::assertSame('ed1a19bb-1', UserId::resolve('ed1a19bb-1'));
        self::assertSame('jean@valjean.fr', UserId::resolve('jean@valjean.fr'));
        self::assertSame('u', UserId::resolve(new Created('u')));
        self::assertSame('c-1', UserId::resolve(Customer::fromArray(CustomerResourceTest::CUSTOMER)));
        self::assertSame('c-1', UserId::resolve(Account::fromArray(['uuid' => 'c-1'])));
        self::assertSame('c-1', UserId::resolve(UserSummary::fromArray(TransactionResourceTest::OWNER)));
        self::assertSame('c-1', UserId::resolve(new OrderUser('c-1', 'jean@valjean.fr')));
    }

    public function testEmptyValuesAreRefused(): void
    {
        foreach (['', ' ', new Created(''), new OrderUser(' ', 'x'), Customer::fromArray([])] as $value) {
            try {
                UserId::resolve($value);
                self::fail('expected an InvalidArgumentException');
            } catch (InvalidArgumentException $e) {
                self::assertSame('user must be a non-empty uuid or email, or a model with a non-empty uuid', $e->getMessage());
            }
        }
    }

    public function testAnyOtherObjectIsATypeError(): void
    {
        // a wrong model — an Order, a Wallet, a plain object with a uuid — is a type error at the call, not an argument error
        self::assertTypeError(fn () => UserId::resolve((object) ['uuid' => 'u'])); // @phpstan-ignore argument.type
        self::assertTypeError(fn () => UserId::resolve(new \stdClass())); // @phpstan-ignore argument.type
        self::assertTypeError(fn () => UserId::resolve(new \Bitgen\Sdk\Model\AssetRef('u', 'ETH', 'Ethereum'))); // @phpstan-ignore argument.type
    }
}
