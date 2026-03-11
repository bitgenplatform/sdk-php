<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Resources;

use Bitgen\Sdk\HttpClient;

use Bitgen\Sdk\Support\UserRef;

use Bitgen\Sdk\Models\UserFull;
use Bitgen\Sdk\Models\UserListItem;
use Bitgen\Sdk\Models\CreatedOrder;
use Bitgen\Sdk\Models\Order;

class OrderResource
{
    public function __construct(private readonly HttpClient $http) {}

    /**
     * Create a buy or sell order.
     *
     * @param array{
     *     user: string,
     *     asset: string,
     *     amount: float|int,
     *     mode: 'BUY'|'SELL'
     * } $params
     */
    public function create(array $params): CreatedOrder
    {
        if (isset($params['user'])) {
            $params['user'] = UserRef::resolve($params['user']);
        }
        $data = $this->http->post('/api/v3/order', $params);
        return CreatedOrder::fromArray($data);
    }

    public function get(string $tunnel): Order
    {
        $data = $this->http->get('/api/v3/order/' . rawurlencode($tunnel));

        return Order::fromArray($data);
    }

    /**
     * @return Order[]
     */
    public function history(string|UserFull|UserListItem $user): array
    {
        $data = $this->http->get('/api/v3/orders/' . rawurlencode(UserRef::resolve($user)));
        return array_map(static fn(array $item) => Order::fromArray($item), $data);
    }
}
