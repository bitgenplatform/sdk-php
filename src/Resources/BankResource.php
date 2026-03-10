<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Resources;

use Bitgen\Sdk\HttpClient;
use Bitgen\Sdk\Models\BankAccount;
use Bitgen\Sdk\Models\BankOperation;
use Bitgen\Sdk\Models\BankWithdrawResult;

class BankResource
{
    public function __construct(private readonly HttpClient $http) {}

    public function get(string $user): BankAccount
    {
        $data = $this->http->get('/api/v3/bank/' . rawurlencode($user));

        return BankAccount::fromArray($data);
    }

    public function withdraw(string $user, float $amount): BankWithdrawResult
    {
        $data = $this->http->put(
            '/api/v3/bank/' . rawurlencode($user),
            ['amount' => $amount],
        );

        return BankWithdrawResult::fromArray($data);
    }

    /**
     * @param array{
     *     direction?: string,
     *     from?: int,
     *     to?: int
     * } $filters
     * @return BankOperation[]
     */
    public function operations(string $user, array $filters = []): array
    {
        $data = $this->http->get(
            '/api/v3/bank/' . rawurlencode($user) . '/operations',
            array_filter($filters, static fn($v) => $v !== null),
        );

        return array_map(static fn(array $item) => BankOperation::fromArray($item), $data);
    }
}
