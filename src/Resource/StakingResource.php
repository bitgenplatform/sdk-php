<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Resource;

use Bitgen\Sdk\Exception\BitgenException;
use Bitgen\Sdk\Http\HttpClient;
use Bitgen\Sdk\Model\Account;
use Bitgen\Sdk\Model\Asset;
use Bitgen\Sdk\Model\AssetRef;
use Bitgen\Sdk\Model\Cast;
use Bitgen\Sdk\Model\Core;
use Bitgen\Sdk\Model\CoreType;
use Bitgen\Sdk\Model\Created;
use Bitgen\Sdk\Model\Customer;
use Bitgen\Sdk\Model\OrderUser;
use Bitgen\Sdk\Model\StakingMovement;
use Bitgen\Sdk\Model\StakingMovementKind;
use Bitgen\Sdk\Model\StakingOperation;
use Bitgen\Sdk\Model\StakingPortfolio;
use Bitgen\Sdk\Model\StakingPosition;
use Bitgen\Sdk\Model\UserSummary;
use Bitgen\Sdk\Page;
use Bitgen\Sdk\Support\Amount;
use Bitgen\Sdk\Support\AssetId;
use Bitgen\Sdk\Support\Enum;
use Bitgen\Sdk\Support\Path;
use Bitgen\Sdk\Support\UserId;
use InvalidArgumentException;

/**
 * `/staking` — place the crypto of a customer with a staking provider (`$client->staking`).
 * Two distinct identifiers: the **movement** (returned by `stake`, handled by `get` / `list` / `movements`)
 * and the **position** (`$movement->staking`, taken by `rewards` / `unstake`) — each by uuid or by model.
 *
 * @phpstan-import-type UserRef from UserId
 */
class StakingResource
{
    public function __construct(private readonly HttpClient $http, private readonly CoreResource $core)
    {
    }

    /**
     * The staking providers: the `STAKING` connectors, optionally for one asset — their `uuid` or `name` is the `$provider` of `stake`
     *
     * @param string|Asset|AssetRef|null $asset only the providers of this asset: uuid, ISO code or model
     *
     * @return Page<Core>
     *
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     * @throws InvalidArgumentException an argument is invalid — nothing was sent
     */
    public function providers(string|Asset|AssetRef|null $asset = null): Page
    {
        return $this->core->list(type: CoreType::STAKING, asset: $asset);
    }

    /**
     * Open a staking position — the amount is moved from the customer's custody wallet to the deposit address of the provider
     *
     * @param UserRef               $user
     * @param string|Asset|AssetRef $asset    uuid or ISO code (`Asset::SOL`), or a model (its uuid is sent)
     * @param string|int|float      $amount   the crypto quantity to stake, as a string, at least the `min_deposit` of the provider
     * @param string                $provider the `uuid` or the `name` of a `STAKING` connector of the organization (`providers()`)
     *
     * @return Created the uuid of the **movement**
     *
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     * @throws InvalidArgumentException an argument is invalid — nothing was sent
     */
    public function stake(string|Created|Customer|Account|UserSummary|OrderUser $user, string|Asset|AssetRef $asset, string|int|float $amount, string $provider): Created
    {
        $body = [
            'user' => UserId::resolve($user),
            'asset' => AssetId::resolve($asset),
            'amount' => Amount::normalize($amount),
            'provider' => $provider,
        ];

        return Created::fromArray(Cast::answer($this->http->post('/staking', $body)));
    }

    /**
     * The movements of the organization, in every state
     *
     * @param UserRef|null $user      only the movements of this customer (unknown → `404 unknown_user`)
     * @param string|null  $direction only this kind of movement: `STAKE`, `UNSTAKE`, `WITHDRAW` or `REWARD` — a `StakingMovementKind` constant
     *
     * @return Page<StakingMovement>
     *
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     * @throws InvalidArgumentException an argument is invalid — nothing was sent
     */
    public function list(string|Created|Customer|Account|UserSummary|OrderUser|null $user = null, ?string $direction = null, ?int $offset = null, ?int $limit = null): Page
    {
        return $this->movementsPage('/staking', $user, $direction, $offset, $limit);
    }

    /**
     * The movements still in progress: `REQUESTED`, `PENDING` and `FAILED` only — same arguments as `list`
     *
     * @param UserRef|null $user      only the movements of this customer (unknown → `404 unknown_user`)
     * @param string|null  $direction only this kind of movement: `STAKE`, `UNSTAKE`, `WITHDRAW` or `REWARD` — a `StakingMovementKind` constant
     *
     * @return Page<StakingMovement>
     *
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     * @throws InvalidArgumentException an argument is invalid — nothing was sent
     */
    public function movements(string|Created|Customer|Account|UserSummary|OrderUser|null $user = null, ?string $direction = null, ?int $offset = null, ?int $limit = null): Page
    {
        return $this->movementsPage('/staking/movements', $user, $direction, $offset, $limit);
    }

    /**
     * One movement, by uuid (the one returned by `stake`) or by model, with its position
     *
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     * @throws InvalidArgumentException an argument is invalid — nothing was sent
     */
    public function get(string|StakingMovement $movement): StakingMovement
    {
        return StakingMovement::fromArray(Cast::answer($this->http->get('/staking/' . Path::segment($movement instanceof StakingMovement ? $movement->uuid : $movement, 'movement'))));
    }

    /**
     * Claim the rewards of a position (`$movement->staking`, or its uuid) — `$amount` absent = all of them.
     * Deducted immediately, the transfer is executed by compliance.
     *
     * @param string|int|float|null $amount the rewards to claim, as a string
     *
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     * @throws InvalidArgumentException an argument is invalid — nothing was sent
     */
    public function rewards(string|StakingPosition $position, string|int|float|null $amount = null): void
    {
        $this->http->put('/staking/' . self::position($position) . '/rewards', self::amountBody($amount));
    }

    /**
     * Leave a position (`$movement->staking`, or its uuid) — `$amount` absent = the whole position (a full exit ignores the minimums).
     * Deducted immediately, the transfer is executed by compliance.
     *
     * @param string|int|float|null $amount the capital to withdraw from the position, as a string
     *
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     * @throws InvalidArgumentException an argument is invalid — nothing was sent
     */
    public function unstake(string|StakingPosition $position, string|int|float|null $amount = null): void
    {
        $this->http->put('/staking/' . self::position($position) . '/unstake', self::amountBody($amount));
    }

    /**
     * The staking operations of a customer
     *
     * @param UserRef $user
     *
     * @return Page<StakingOperation>
     *
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     * @throws InvalidArgumentException an argument is invalid — nothing was sent
     */
    public function operations(string|Created|Customer|Account|UserSummary|OrderUser $user, ?int $offset = null, ?int $limit = null): Page
    {
        return Page::fromArray(
            Cast::answer($this->http->get('/staking/' . self::user($user) . '/operations', ['offset' => $offset, 'limit' => $limit])),
            StakingOperation::fromArray(...),
        );
    }

    /**
     * The EUR balances and curves (capital, revenues) of a customer's staking
     *
     * @param UserRef $user
     *
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     * @throws InvalidArgumentException an argument is invalid — nothing was sent
     */
    public function portfolio(string|Created|Customer|Account|UserSummary|OrderUser $user): StakingPortfolio
    {
        return StakingPortfolio::fromArray(Cast::answer($this->http->get('/staking/' . self::user($user) . '/portfolio')));
    }

    /**
     * `GET /staking` or `GET /staking/movements` — a `direction` outside `StakingMovementKind::VALUES` is refused before any request
     *
     * @param UserRef|null $user
     *
     * @return Page<StakingMovement>
     *
     * @throws BitgenException
     * @throws InvalidArgumentException
     */
    private function movementsPage(string $path, string|Created|Customer|Account|UserSummary|OrderUser|null $user, ?string $direction, ?int $offset, ?int $limit): Page
    {
        $query = [
            'user' => $user === null ? null : UserId::resolve($user),
            'direction' => $direction === null ? null : Enum::ensure($direction, StakingMovementKind::VALUES, 'direction'),
            'offset' => $offset,
            'limit' => $limit,
        ];

        return Page::fromArray(Cast::answer($this->http->get($path, $query)), StakingMovement::fromArray(...));
    }

    /**
     * `{ amount }` when given, `{}` otherwise — the API reads an absent amount as "everything"
     *
     * @return array<string, string>
     *
     * @throws InvalidArgumentException
     */
    private static function amountBody(string|int|float|null $amount): array
    {
        return $amount === null ? [] : ['amount' => Amount::normalize($amount)];
    }

    private static function position(string|StakingPosition $position): string
    {
        return Path::segment($position instanceof StakingPosition ? $position->uuid : $position, 'position');
    }

    /** @param UserRef $user */
    private static function user(string|Created|Customer|Account|UserSummary|OrderUser $user): string
    {
        return Path::segment(UserId::resolve($user), 'user');
    }
}
