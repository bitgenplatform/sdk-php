<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Resources;

use Bitgen\Sdk\HttpClient;

use Bitgen\Sdk\Support\UserRef;

use Bitgen\Sdk\Models\UserFull;
use Bitgen\Sdk\Models\UserListItem;
use Bitgen\Sdk\Models\CreatedTransaction;
use Bitgen\Sdk\Models\Transaction;
use Bitgen\Sdk\Models\TransactionHistoryItem;

class TransactionResource
{
    public function __construct(private readonly HttpClient $http) {}

    /**
     * Create a crypto withdrawal transaction.
     *
     * @param array{
     *     user: string,
     *     asset: string,
     *     amount: float|int,
     *     targetAddress: string,
     *     targetTag?: string
     * } $params
     */
    public function create(array $params): CreatedTransaction
    {
        if (isset($params['user'])) {
            $params['user'] = UserRef::resolve($params['user']);
        }
        $data = $this->http->postRaw('/api/v3/tx', $params);
        return CreatedTransaction::fromArray($data);
    }

    public function get(string $txId): Transaction
    {
        $data = $this->http->get('/api/v3/tx/' . rawurlencode($txId));

        return Transaction::fromArray($data);
    }

    /**
     * @param array{
     *     asset?: string,
     *     direction?: string,
     *     from?: int,
     *     to?: int
     * } $filters
     * @return TransactionHistoryItem[]
     */
    public function history(string|UserFull|UserListItem $user, array $filters = []): array
    {
        $data = $this->http->get(
            '/api/v3/txs/' . rawurlencode(UserRef::resolve($user)),
            array_filter($filters, static fn($v) => $v !== null),
        );
        return array_map(
            static fn(array $item) => TransactionHistoryItem::fromArray($item),
            $data,
        );
    }
}
