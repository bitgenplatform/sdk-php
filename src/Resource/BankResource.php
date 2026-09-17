<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Resource;

use Bitgen\Sdk\Exception\BitgenException;
use Bitgen\Sdk\Http\HttpClient;
use Bitgen\Sdk\Model\Account;
use Bitgen\Sdk\Model\BankAccount;
use Bitgen\Sdk\Model\BankDirection;
use Bitgen\Sdk\Model\BankOperation;
use Bitgen\Sdk\Model\BankWithdrawal;
use Bitgen\Sdk\Model\Cast;
use Bitgen\Sdk\Model\Created;
use Bitgen\Sdk\Model\Customer;
use Bitgen\Sdk\Model\OrderUser;
use Bitgen\Sdk\Model\UserSummary;
use Bitgen\Sdk\Page;
use Bitgen\Sdk\Support\Amount;
use Bitgen\Sdk\Support\Enum;
use Bitgen\Sdk\Support\Path;
use Bitgen\Sdk\Support\UserId;
use InvalidArgumentException;

/**
 * `/bank` — the EUR account of each customer (`$client->bank`).
 *
 * @phpstan-import-type UserRef from UserId
 */
class BankResource
{
    public function __construct(private readonly HttpClient $http)
    {
    }

    /**
     * The EUR account of a customer — created on first read
     *
     * @param UserRef $user
     *
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     * @throws InvalidArgumentException an argument is invalid — nothing was sent
     */
    public function get(string|Created|Customer|Account|UserSummary|OrderUser $user): BankAccount
    {
        return BankAccount::fromArray(Cast::answer($this->http->get('/bank/' . self::user($user))));
    }

    /**
     * The EUR operations of a customer
     *
     * @param UserRef     $user
     * @param string|null $direction `ALL` (default), `DEPOSIT`, `WITHDRAWAL`, `PURCHASE` or `SELL` — a `BankDirection` constant
     * @param int|null    $from      epoch seconds — with `$to`, otherwise ignored
     * @param int|null    $to        epoch seconds — with `$from`, otherwise ignored
     *
     * @return Page<BankOperation>
     *
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     * @throws InvalidArgumentException an argument is invalid — nothing was sent
     */
    public function operations(string|Created|Customer|Account|UserSummary|OrderUser $user, ?string $direction = null, ?int $from = null, ?int $to = null, ?int $offset = null, ?int $limit = null): Page
    {
        $query = [
            'direction' => $direction === null ? null : Enum::ensure($direction, BankDirection::VALUES, 'direction'),
            'from' => $from,
            'to' => $to,
            'offset' => $offset,
            'limit' => $limit,
        ];

        return Page::fromArray(Cast::answer($this->http->get('/bank/' . self::user($user) . '/operations', $query)), BankOperation::fromArray(...));
    }

    /**
     * Withdraw EUR to the customer's IBAN — `$iban` / `$bank` / `$bic` update the bank details first
     *
     * @param UserRef          $user
     * @param string|int|float $amount EUR, rounded to 2 decimals by the API
     *
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     * @throws InvalidArgumentException an argument is invalid — nothing was sent
     */
    public function withdraw(string|Created|Customer|Account|UserSummary|OrderUser $user, string|int|float $amount, ?string $iban = null, ?string $bank = null, ?string $bic = null): BankWithdrawal
    {
        $body = array_filter(['amount' => Amount::normalize($amount), 'iban' => $iban, 'bank' => $bank, 'bic' => $bic], static fn (mixed $value): bool => $value !== null);

        return BankWithdrawal::fromArray(Cast::answer($this->http->put('/bank/' . self::user($user), $body)));
    }

    /**
     * Declare an EUR deposit (manual bank provider only) — the account is designated by `$user` or by the wire `$message`;
     * `$reference` (the bank's transfer reference) makes the call idempotent
     *
     * @param string|int|float $amount EUR
     * @param UserRef|null     $user
     *
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     * @throws InvalidArgumentException an argument is invalid — nothing was sent
     */
    public function credit(string|int|float $amount, string|Created|Customer|Account|UserSummary|OrderUser|null $user = null, ?string $message = null, ?string $reference = null, ?string $currency = null): Created
    {
        $body = array_filter([
            'amount' => Amount::normalize($amount),
            'currency' => $currency,
            'user' => $user === null ? null : UserId::resolve($user),
            'message' => $message,
            'reference' => $reference,
        ], static fn (mixed $value): bool => $value !== null);

        return Created::fromArray(Cast::answer($this->http->post('/bank', $body)));
    }

    /** @param UserRef $user */
    private static function user(string|Created|Customer|Account|UserSummary|OrderUser $user): string
    {
        return Path::segment(UserId::resolve($user), 'user');
    }
}
