<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Resources;

use Bitgen\Sdk\HttpClient;

use Bitgen\Sdk\Support\UserRef;

use Bitgen\Sdk\Models\UserFull;
use Bitgen\Sdk\Models\UserListItem;
use Bitgen\Sdk\Models\CreatedUser;
use Bitgen\Sdk\Models\UserDetail;
use Bitgen\Sdk\Models\UserList;

class UserResource
{
    public function __construct(private readonly HttpClient $http) {}

    /**
     * @param array{
     *     email: string,
     *     iban: string,
     *     firstname: string,
     *     lastname: string
     * } $params
     */
    public function create(array $params): CreatedUser
    {
        $data = $this->http->post('/api/v3/user', $params);

        return CreatedUser::fromArray($data);
    }

    /**
     * @param array{offset: int, limit: int} $params
     */
    public function list(array $params): UserList
    {
        $data = $this->http->get('/api/v3/user', $params);

        return UserList::fromArray($data);
    }

    public function get(string|UserFull|UserListItem $user): UserDetail
    {
        $data = $this->http->get('/api/v3/user/' . rawurlencode(UserRef::resolve($user)));
        return UserDetail::fromArray($data);
    }

    /**
     * @param array{email?: string} $params
     */
    public function update(string|UserFull|UserListItem $user, array $params): void
    {
        $this->http->put('/api/v3/user/' . rawurlencode(UserRef::resolve($user)), $params);
    }

    public function disable(string|UserFull|UserListItem $user): void
    {
        $this->http->delete('/api/v3/user/' . rawurlencode(UserRef::resolve($user)));
    }
}
