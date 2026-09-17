# bitgen/sdk — v1.0.1

Official PHP SDK for the BITGEN API v4 — server-side, PHP 8.2+, no dependency beyond `ext-curl` and `ext-json`.
Install it with `composer require bitgen/sdk`.

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

- [Installation](readme/installation.md) — PHP 8.2+, Composer, the classes to import
- [Quick start](readme/quick-start.md) — a customer, their EUR account, a wallet, a purchase
- [Configuration](readme/configuration.md) — credentials, environments, custom host, timeout
- [Concepts](readme/concepts.md) — user references, amounts, pagination, assets, activation
- [Errors](readme/errors.md) — `BitgenException`, error codes, invalid arguments

Resources, in the order of an integration:

- [Customers](readme/resource/customer.md) — `$client->customer`
- [Bank accounts](readme/resource/bank.md) — `$client->bank`
- [Custody wallets](readme/resource/custody.md) — `$client->custody`
- [Trading](readme/resource/trading.md) — `$client->trading`
- [Transactions](readme/resource/transaction.md) — `$client->transaction`
- [Staking](readme/resource/staking.md) — `$client->staking`
- [Connectors](readme/resource/core.md) — `$client->core`
- [Webhooks](readme/resource/webhooks.md) — `$client->webhooks`
- [API keys](readme/resource/apikeys.md) — `$client->apikeys`
- [Assets](readme/resource/asset.md) — `$client->asset`

## License

Private — © BITGEN
