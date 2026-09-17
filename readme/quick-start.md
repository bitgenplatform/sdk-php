# Quick start

Create the client once, then run a first journey: create a customer, read their EUR account, open a wallet, buy crypto.

## 1. Create the client

```php
<?php

use Bitgen\Sdk\BitgenClient;
use Bitgen\Sdk\Env;

$client = new BitgenClient(
    scope: 'YOUR_SCOPE_UUID',   // uuid of the organization that owns the key
    apiKey: 'YOUR_API_KEY',
    env: Env::SANDBOX,          // Env::PRODUCTION by default
);
```

One instance per API key, reused for every call. `scope`, `apiKey`, environments and the other options are detailed in [Configuration](configuration.md).

## 2. Create a customer

```php
<?php

use Bitgen\Sdk\Model\Locale;

$customer = $client->customer->create(
    email: 'jean@valjean.fr',
    manager: 'MANAGER_UUID',   // the collaborator of your organization who follows this customer
    firstname: 'Jean',
    lastname: 'Valjean',
    locale: Locale::FR,
);
```

The customer receives an activation email: until they click it, their account stays `CREATED` and the bank, custody, trading and staking resources do not see it (unless you create them with `needActivation: false` — [Customers › create](resource/customer.md#create)). If your organization uses BITGEN's identity verification, their identity must also be validated before the next steps ([Activation and identity](concepts.md#activation-and-identity)). The `Created` object the API returned carries the customer's `uuid`: the next steps pass it as is, wherever a customer is expected ([User references](concepts.md#user-references)).

## 3. Read the EUR account

```php
<?php

$account = $client->bank->get($customer);

echo $account->message, PHP_EOL;   // wire reference: the customer puts it on their bank transfer
echo $account->balance, PHP_EOL;   // EUR balance, credited once the transfer is received
```

The EUR account is created on first read. Balance, operations and withdrawals: [Bank accounts](resource/bank.md).

## 4. Open a wallet

```php
<?php

use Bitgen\Sdk\Asset;

$wallet = $client->custody->wallet($customer, Asset::ETH);

echo $wallet->address, PHP_EOL;   // deposit address, created at the custodian on first read
echo $wallet->balance, PHP_EOL;   // exact quantity, as a string
```

Wallets, balances and on-chain withdrawals: [Custody wallets](resource/custody.md).

## 5. Buy crypto

```php
<?php

use Bitgen\Sdk\Asset;

$created = $client->trading->buy(
    $customer,
    Asset::ETH,
    '25.00',                 // EUR, taken from the customer's EUR account
    reference: 'order-42',   // optional idempotency key
);

$order = $client->trading->get($created->tunnel);   // `tunnel` is the order uuid; `$order->state` follows its lifecycle
```

Orders, states and sales: [Trading](resource/trading.md).

## Handling errors

An error of the API throws a `BitgenException` carrying the HTTP `status` and a stable `errorCode`:

```php
<?php

use Bitgen\Sdk\Exception\BitgenException;

try {
    $client->bank->withdraw($customer, '50.00');
} catch (BitgenException $e) {
    if ($e->errorCode === 'requested_amount_error') {
        // insufficient EUR balance
    }
}
```

All the details, including the errors that happen before any request is sent: [Errors](errors.md).
