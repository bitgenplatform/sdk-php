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
use Bitgen\Sdk\Model\Customer;
use Bitgen\Sdk\Model\OrderUser;
use Bitgen\Sdk\Model\Transaction;
use Bitgen\Sdk\Model\TransactionDirection;
use Bitgen\Sdk\Model\TransactionSource;
use Bitgen\Sdk\Model\TransactionState;
use Bitgen\Sdk\Model\UserSummary;
use Bitgen\Sdk\Page;
use Bitgen\Sdk\Support\AssetId;
use Bitgen\Sdk\Support\Enum;
use Bitgen\Sdk\Support\Path;
use Bitgen\Sdk\Support\UserId;
use InvalidArgumentException;

/**
 * `/transaction` — the unified journal of the fiat and crypto movements of the organization, read-only (`$client->transaction`).
 *
 * @phpstan-import-type UserRef from UserId
 */
class TransactionResource
{
    public function __construct(private readonly HttpClient $http)
    {
    }

    /**
     * The transactions of the organization, optionally filtered — `$limit` up to 100 on this route
     *
     * @param UserRef|null               $user      only the transactions of this customer (unknown → `404 unknown_user`)
     * @param string|null                $status    only this state — a `TransactionState` constant
     * @param string|null                $source    `BANK` or `CUSTODY` — a `TransactionSource` constant
     * @param string|null                $direction `IN` or `OUT` — a `TransactionDirection` constant
     * @param string|Asset|AssetRef|null $asset     only this asset (ISO code, uuid or model)
     *
     * @return Page<Transaction>
     *
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     * @throws InvalidArgumentException an argument is invalid — nothing was sent
     */
    public function list(
        string|Created|Customer|Account|UserSummary|OrderUser|null $user = null,
        ?string $status = null,
        ?string $source = null,
        ?string $direction = null,
        string|Asset|AssetRef|null $asset = null,
        ?int $offset = null,
        ?int $limit = null,
    ): Page {
        $query = [
            'user' => $user === null ? null : UserId::resolve($user),
            'status' => $status === null ? null : Enum::ensure($status, TransactionState::VALUES, 'status'),
            'source' => $source === null ? null : Enum::ensure($source, TransactionSource::VALUES, 'source'),
            'direction' => $direction === null ? null : Enum::ensure($direction, TransactionDirection::VALUES, 'direction'),
            'asset' => $asset === null ? null : AssetId::resolve($asset),
            'offset' => $offset,
            'limit' => $limit,
        ];

        return Page::fromArray(Cast::answer($this->http->get('/transaction', $query)), Transaction::fromArray(...));
    }

    /**
     * One transaction, by uuid, by reference, or by model
     *
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     * @throws InvalidArgumentException an argument is invalid — nothing was sent
     */
    public function get(string|Transaction $transaction): Transaction
    {
        return Transaction::fromArray(Cast::answer($this->http->get('/transaction/' . Path::segment($transaction instanceof Transaction ? $transaction->uuid : $transaction, 'transaction'))));
    }
}
