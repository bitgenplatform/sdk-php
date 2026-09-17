# Transactions

The transaction journal is the unified, read-only record of the fiat and crypto movements of your organization: bank deposits and withdrawals, custody deposits and withdrawals, and the internal legs of sales and staking. Transactions are created by the platform — never through the API — and referenced by the other resources: `$client->bank->withdraw()` and `$client->custody->withdraw()` return the uuid of the transaction they create. `$client->transaction` lists and reads them.

Examples use `$client`, a configured `BitgenClient` ([Configuration](../configuration.md)), and `$customer`, the `Created` returned by `$client->customer->create()`.

## Methods

| Method | What it does | Returns |
|---|---|---|
| `list(...)` | Lists the transactions of your organization | `Page<Transaction>` |
| `get($transaction)` | Reads one transaction, by uuid or reference | `Transaction` |

Models of this resource, under `Bitgen\Sdk\Model`: `Transaction`, `TransactionAlert`, `UserSummary`, `UserSummaryAccount`, `OrganizationSummary`, `OrganizationHub` — and the constant classes `TransactionState`, `TransactionSource`, `TransactionDirection`.

## list

```
$client->transaction->list(?UserRef $user = null, ?string $status = null, ?string $source = null, ?string $direction = null, string|Model\Asset|AssetRef|null $asset = null, ?int $offset = null, ?int $limit = null): Page<Transaction>
```

| Argument | Type | Description |
|---|---|---|
| `user` | `?UserRef` | Only the transactions of this customer (uuid or model; unknown → `404 unknown_user`) |
| `status` | `?string` | Only this state: `TransactionState::ANALYZING`, `PENDING`, `COMPLETED`, `FROZEN`, `FAILED`, `TRANSFERING` (the API's spelling) or `SEIZED` — anything else is refused before any request |
| `source` | `?string` | `TransactionSource::BANK` or `TransactionSource::CUSTODY` |
| `direction` | `?string` | `TransactionDirection::IN` or `TransactionDirection::OUT` |
| `asset` | `string\|Model\Asset\|AssetRef\|null` | Only this asset, by ISO code, uuid or model |
| `offset`, `limit` | `?int` | [Pagination](../concepts.md#pagination) — `limit` up to 100 on this list |

```php
<?php

use Bitgen\Sdk\Asset;
use Bitgen\Sdk\Model\TransactionDirection;
use Bitgen\Sdk\Model\TransactionSource;
use Bitgen\Sdk\Model\TransactionState;

$page = $client->transaction->list(
    user: $customer,
    status: TransactionState::PENDING,
    source: TransactionSource::CUSTODY,
    direction: TransactionDirection::OUT,
    asset: Asset::ETH,
    offset: 0,
    limit: 100,
);

foreach ($page->items as $transaction) {
    echo $transaction->direction, ' ', $transaction->asset, ' ', $transaction->amount, ' ', $transaction->state, PHP_EOL;
}
```

Returns a page of `Transaction`.

## get

```
$client->transaction->get(string|Transaction $transaction): Transaction
```

`$transaction` is the uuid of the transaction, its `reference`, or a `Transaction`.

```php
<?php

$withdrawal = $client->bank->withdraw($customer, '50.00');

$transaction = $client->transaction->get($withdrawal->transaction);
echo $transaction->state, PHP_EOL;   // PENDING
```

Returns a `Transaction`:

| Property | Description |
|---|---|
| `uuid` | The transaction |
| `state` | `TransactionState::ANALYZING`, `PENDING`, `COMPLETED`, `FROZEN`, `FAILED`, `TRANSFERING` or `SEIZED` — a string |
| `source` | `TransactionSource::BANK` (EUR) or `TransactionSource::CUSTODY` (crypto) — a string |
| `direction` | `TransactionDirection::IN` or `TransactionDirection::OUT` — a string |
| `asset` | The asset ISO code — `EUR` for a bank transaction |
| `amount` | The amount, in that asset (a `float` — for crypto, the exact amount is the string held by the custody wallet) |
| `eurValue` | EUR value when recorded, or `null` |
| `reference` | The reference, or `null` |
| `credited` | Whether the customer's balance (EUR account or wallet) has been credited |
| `silent` | `true` for an internal leg (staking, sale) that is not an operation of the customer |
| `data` | Additional context set by the platform (compliance details, internal flags), an associative array — varies with the transaction, not needed for an integration |
| `createdAt`, `updatedAt` | Epoch seconds |
| `owner` | The customer, a `UserSummary`: `uuid`, `state`, `login`, `account` (`UserSummaryAccount`: `firstname`, `lastname`, `fin`) — or `null` |
| `assignee` | The compliance officer assigned while the transaction is on hold (`PENDING`, `FROZEN`), a `UserSummary`; `null` otherwise |
| `organization` | `OrganizationSummary`: `uuid`, `state`, `name`, `hub` — the hub the organization belongs to (`OrganizationHub`: `uuid`, `name`, or `null`) — or `null` |
| `alert` | The compliance alert attached to the transaction (`TransactionAlert`), or `null`: `uuid`, `state` (`OPEN`, `RESOLVED`, `DISMISSED`, `DECLARATED`, `CONFIRMED`), `severity` (`SUCCESS`, `WARNING`, `CRITICAL`), `type` (`KYT`, `KYC_EXPIRE`, `SUSPICIOUS_ACTIVITY`, `AML`, `SANCTIONS`), `description`, `confidence` (confidence of the analysis, 0–100), `recommendation` (suggested action), `factors` (elements that weighed in the analysis), `sources` (the observations analysed), `history` (state changes of the alert), `incidentKey` (groups the alerts of a same incident), `createdAt`, `updatedAt`, `user` (the customer), `assignee` (the compliance officer), `organization` — `factors`, `history`, `user`, `assignee` and `organization` are kept as the API gives them (`mixed`) |

An unknown uuid or reference, or a transaction outside your organization, answers `404 unknown_transaction`.

## Errors

In addition to the [common errors](../errors.md#common-errors):

| Status | `errorCode` | Meaning |
|---|---|---|
| `400` | `invalid_transaction_state` | `status` is not one of the transaction states |
| `404` | `unknown_user` | `list`: unknown `user` |
| `404` | `unknown_transaction` | Unknown uuid or reference, or outside your organization |

## Related

- [Bank accounts](bank.md) — EUR withdrawals return a transaction uuid
- [Custody wallets](custody.md) — on-chain withdrawals return a transaction uuid
- [Pagination](../concepts.md#pagination) — `limit` up to 100 on this list
- [Webhooks](webhooks.md) — `bank.transaction`, `custody.transaction`, `alert.opened`, `alert.status`
