<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Resource;

use Bitgen\Sdk\Exception\BitgenException;
use Bitgen\Sdk\Http\HttpClient;
use Bitgen\Sdk\Model\Account;
use Bitgen\Sdk\Model\Asset;
use Bitgen\Sdk\Model\AssetRef;
use Bitgen\Sdk\Model\Cast;
use Bitgen\Sdk\Model\Created;
use Bitgen\Sdk\Model\CustodyPortfolio;
use Bitgen\Sdk\Model\CustodyWithdrawal;
use Bitgen\Sdk\Model\Customer;
use Bitgen\Sdk\Model\OrderUser;
use Bitgen\Sdk\Model\TravelRule;
use Bitgen\Sdk\Model\UserSummary;
use Bitgen\Sdk\Model\Wallet;
use Bitgen\Sdk\Support\Amount;
use Bitgen\Sdk\Support\AssetId;
use Bitgen\Sdk\Support\Path;
use Bitgen\Sdk\Support\UserId;
use InvalidArgumentException;

/**
 * `/custody` — the crypto wallets of each customer, per asset (`$client->custody`).
 * `$user` is a customer — or the uuid of the organization (the scope) for its treasury wallets, read-only.
 *
 * @phpstan-import-type UserRef from UserId
 */
class CustodyResource
{
    public function __construct(private readonly HttpClient $http)
    {
    }

    /**
     * The wallets of a customer (or of the organization), without their `history`
     *
     * @param UserRef $user
     *
     * @return list<Wallet>
     *
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     * @throws InvalidArgumentException an argument is invalid — nothing was sent
     */
    public function wallets(string|Created|Customer|Account|UserSummary|OrderUser $user): array
    {
        return array_map(Wallet::fromArray(...), Cast::answerList($this->http->get('/custody/' . self::user($user))));
    }

    /**
     * One wallet by asset uuid or ISO code, with its `history`. A missing wallet is provisioned on first read
     * (deposit address created at the custodian): the key then needs `custody.write`.
     *
     * @param UserRef               $user
     * @param string|Asset|AssetRef $asset uuid or ISO code (`Asset::ETH`), or a model (its uuid is sent)
     *
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     * @throws InvalidArgumentException an argument is invalid — nothing was sent
     */
    public function wallet(string|Created|Customer|Account|UserSummary|OrderUser $user, string|Asset|AssetRef $asset): Wallet
    {
        return Wallet::fromArray(Cast::answer($this->http->get('/custody/' . self::user($user) . '/' . self::asset($asset))));
    }

    /**
     * The EUR value curve of the whole custody of a customer (customers only)
     *
     * @param UserRef $user
     *
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     * @throws InvalidArgumentException an argument is invalid — nothing was sent
     */
    public function portfolio(string|Created|Customer|Account|UserSummary|OrderUser $user): CustodyPortfolio
    {
        return CustodyPortfolio::fromArray(Cast::answer($this->http->get('/custody/' . self::user($user) . '/portfolio')));
    }

    /**
     * On-chain withdrawal to an external address (customers only). The amount reaches the API untouched — never
     * rounded or reformatted. `transaction` is null while the analysis has not created the line yet.
     *
     * @param UserRef               $user
     * @param string|Asset|AssetRef $asset          uuid or ISO code (`Asset::ETH`), or a model (its uuid is sent)
     * @param string|int|float      $amount         the quantity to send, as a string: more than 0, at most `baseUnit` decimals
     * @param string|null           $targetTag      the destination memo / tag, for the assets that need one
     * @param string|null           $idempotencyKey 64 characters max, unique per customer: replaying it returns the same transaction
     * @param TravelRule|null       $travelRule     a `TravelRulePerson` or a `TravelRulePlatform`
     *
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     * @throws InvalidArgumentException an argument is invalid — nothing was sent
     */
    public function withdraw(
        string|Created|Customer|Account|UserSummary|OrderUser $user,
        string|Asset|AssetRef $asset,
        string|int|float $amount,
        string $targetAddress,
        ?string $targetTag = null,
        ?string $idempotencyKey = null,
        ?TravelRule $travelRule = null,
    ): CustodyWithdrawal {
        $body = array_filter([
            'asset' => AssetId::resolve($asset),
            'amount' => Amount::normalize($amount),
            'targetAddress' => $targetAddress,
            'targetTag' => $targetTag,
            'idempotencyKey' => $idempotencyKey,
            'travelRule' => $travelRule?->toArray(),
        ], static fn (mixed $value): bool => $value !== null);

        return CustodyWithdrawal::fromArray(Cast::answer($this->http->put('/custody/' . self::user($user), $body)));
    }

    /** @param UserRef $user */
    private static function user(string|Created|Customer|Account|UserSummary|OrderUser $user): string
    {
        return Path::segment(UserId::resolve($user), 'user');
    }

    private static function asset(string|Asset|AssetRef $asset): string
    {
        return Path::segment(AssetId::resolve($asset), 'asset');
    }
}
