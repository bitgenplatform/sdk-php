<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Models;

readonly class UserDetail
{
    /**
     * @param Wallet[] $activeWallets
     */
    public function __construct(
        public UserFull    $user,
        public array       $activeWallets,
        public BankAccount $bank,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            user:          UserFull::fromArray($data['user']),
            activeWallets: array_map(
                static fn(array $w) => Wallet::fromArray($w),
                $data['active_wallets'],
            ),
            bank:          BankAccount::fromArray($data['bank']),
        );
    }
}
