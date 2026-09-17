# Custody wallets

A custody wallet holds the crypto of a customer for one asset, at the custodian of the platform: a deposit address the customer sends funds to, an exact balance, and on-chain withdrawals to external addresses. `$client->custody` lists and reads the wallets of a customer, provisions them, reads the EUR value of their custody, and withdraws.

Examples use `$client`, a configured `BitgenClient` ([Configuration](../configuration.md)), and `$customer`, the `Created` returned by `$client->customer->create()`. A customer is designated by a `UserRef`: their uuid, or a model carrying it ([User references](../concepts.md#user-references)). `wallets` and `wallet` also take the uuid of your organization (your `scope`) to read its treasury wallets (`type` `TREASURY`, read-only); `portfolio` and `withdraw` are for customers only. A customer who is not an activated member of your organization is refused with `403 org_forbidden` ([Activation and identity](../concepts.md#activation-and-identity)); another organization, with `403 cross_org_forbidden`.

## Methods

| Method | What it does | Returns |
|---|---|---|
| `wallets($user)` | Lists the wallets of a customer, or of your organization | `Wallet[]` |
| `wallet($user, $asset)` | Reads one wallet with its EUR value curve — provisions it on first read | `Wallet` |
| `portfolio($user)` | Reads the EUR value curve of the whole custody of a customer | `CustodyPortfolio` |
| `withdraw($user, $asset, $amount, $targetAddress, ...)` | Sends crypto from a wallet to an external address | `CustodyWithdrawal` |

Models of this resource, under `Bitgen\Sdk\Model`: `Wallet`, `AssetRef`, `CustodyPortfolio`, `CustodyWithdrawal`, `TravelRule` (`TravelRulePerson`, `TravelRulePlatform`) — the constant classes `WalletState`, `WalletType`, and the shared `History`.

## wallets

```
$client->custody->wallets(UserRef $user): Wallet[]
```

```php
<?php

$wallets = $client->custody->wallets($customer);

foreach ($wallets as $wallet) {
    echo $wallet->asset->iso, ' ', $wallet->balance, ' ', $wallet->address, PHP_EOL;   // ETH 0.5 0xabc…
}

$treasury = $client->custody->wallets('YOUR_SCOPE_UUID');   // the treasury wallets of your organization
```

Returns a plain PHP array of `Wallet` (a `list<Wallet>`, not a `Page`: the API answers the whole list), without their `history`:

| Property | Description |
|---|---|
| `uuid` | The wallet |
| `state` | `WalletState::CREATED` or `WalletState::FROZEN` — a string |
| `type` | `WalletType::USER` for a customer, `WalletType::TREASURY` for your organization — a string |
| `address` | The deposit address (or `null`) |
| `addressLegacy` | The same deposit address in the legacy format of the chain, for networks that have two address formats; `null` otherwise |
| `tag` | The memo / tag of the address, for the assets that use one (XRP, XLM…) — or `null` |
| `balance` | The exact quantity, as a string |
| `asset` | `AssetRef`: `uuid`, `iso`, `label` ([Assets](../concepts.md#assets) — compare `iso` case-insensitively) |
| `history` | Only on `wallet`: the EUR value curve, a `History` ([Timestamps and histories](../concepts.md#timestamps-and-histories)) — `null` here, and on a new wallet until the curve has been computed |

## wallet

```
$client->custody->wallet(UserRef $user, string|Model\Asset|AssetRef $asset): Wallet
```

| Argument | Type | Description |
|---|---|---|
| `asset` | `string\|Model\Asset\|AssetRef` | The asset, by uuid or ISO code (`Asset::ETH`) or by model ([Assets](../concepts.md#assets)) — never the uuid of the wallet |

```php
<?php

use Bitgen\Sdk\Asset;

$wallet = $client->custody->wallet($customer, Asset::ETH);

// Show the customer where to send their ETH
echo $wallet->address, PHP_EOL;                 // 0xabc…
var_dump($wallet->tag);                         // NULL — a memo / tag only for assets that need one
echo $wallet->balance, PHP_EOL;                 // 0.5
echo count($wallet->history?->d ?? []), PHP_EOL;   // 24 — EUR value over the last 24 hours, only on this unit read
```

When the customer has no wallet for this asset yet, the API **provisions** it: a deposit address is created at the custodian. The customer must be activated, not frozen (`403 account_frozen`), with a validated identity if your organization uses BITGEN's identity verification (`403 kyc_not_validated` — [Activation and identity](../concepts.md#activation-and-identity)) and no active compliance alert (`423 blocked_by_alert`). Returns the `Wallet` with its `history`.

## portfolio

```
$client->custody->portfolio(UserRef $user): CustodyPortfolio
```

```php
<?php

$portfolio = $client->custody->portfolio($customer);

foreach ($portfolio->history->m as [$epoch, $value]) {   // EUR value of the custody, one point per day over the last month
    echo date('Y-m-d', $epoch), ' ', $value, PHP_EOL;
}
```

Returns a `CustodyPortfolio`: `uuid`, the custody account of the customer, `type`, `WalletType::USER` (a customer) or `WalletType::TREASURY` (the organization), and `history`, the EUR value curve — `uuid` and `type` are `null`, and the curve is at zero, while the customer has no custody yet. Customers only: your organization's uuid is refused with `415 custody_portfolio_treasury_unsupported`.

## withdraw

```
$client->custody->withdraw(UserRef $user, string|Model\Asset|AssetRef $asset, string|int|float $amount, string $targetAddress, ?string $targetTag = null, ?string $idempotencyKey = null, ?TravelRule $travelRule = null): CustodyWithdrawal
```

| Argument | Type | Description |
|---|---|---|
| `asset` | `string\|Model\Asset\|AssetRef` | The asset, by uuid or ISO code (`Asset::ETH`) or by model |
| `amount` | `string\|int\|float` | The quantity to send, as a string: more than 0, at most the decimals of the asset (`baseUnit`) — sent untouched; the EUR value of the quantity must reach a minimum — `416 withdraw_below_minimum` below it ([Amounts](../concepts.md#amounts)) |
| `targetAddress` | `string` | The destination address |
| `targetTag` | `?string` | The destination memo / tag, for the assets that need one |
| `idempotencyKey` | `?string` | Optional, 64 characters max, unique per customer: replaying the same key returns the same transaction |
| `travelRule` | `?TravelRule` | Optional travel rule information on the destination: a person, `new TravelRulePerson(firstname:, lastname:, address:)` (at least one of them), **or** a platform, `new TravelRulePlatform('Kraken')` — one form or the other, 255 characters max per field |

```php
<?php

use Bitgen\Sdk\Asset;
use Bitgen\Sdk\Model\TravelRulePlatform;

$withdrawal = $client->custody->withdraw(
    $customer,
    Asset::ETH,
    '0.05',                   // string: up to 18 decimals, sent as is — above the asset's minimum (see Errors)
    '0xdef…',
    idempotencyKey: 'withdraw-42',
    travelRule: new TravelRulePlatform('Kraken'),   // or new TravelRulePerson(firstname: 'Jean', lastname: 'Valjean', address: '…')
);

if ($withdrawal->transaction !== null) {
    $transaction = $client->transaction->get($withdrawal->transaction);   // follow it in the transaction journal
    echo $transaction->state, PHP_EOL;                                   // PENDING
}
```

Customers only: your organization's uuid is refused with `415 custody_treasury_withdraw_unsupported`. The EUR value of the withdrawal must reach the minimum (`416 withdraw_below_minimum` — [Amounts](../concepts.md#amounts)). Returns a `CustodyWithdrawal`: `transaction`, the uuid of the `Transaction` created for the withdrawal ([Transactions](transaction.md)) — `null` while the analysis has not created it yet.

## Errors

In addition to the [common errors](../errors.md#common-errors):

| Status | `errorCode` | Meaning |
|---|---|---|
| `400` | `invalid_amount` | The amount is not valid |
| `400` | `invalid_travel_rule` | `travelRule` mixes the two forms, or a field is too long |
| `403` | `org_forbidden` | The customer is not an activated member of your organization |
| `403` | `cross_org_forbidden` | The uuid belongs to another organization |
| `403` | `account_frozen` | The customer's account is frozen |
| `403` | `kyc_not_validated` | Your organization uses BITGEN's identity verification and the customer's identity is not validated ([Activation and identity](../concepts.md#activation-and-identity)) |
| `403` | `wallet_frozen` | The wallet is frozen |
| `403` | `user_actions_disabled` | Customer actions are disabled for your organization (`user_can_actions` flag) |
| `404` | `unknown_asset` | Unknown asset — the wallet uuid is not accepted |
| `404` | `unknown_organization` | The organization is unknown |
| `404` | `withdraw_organization_unresolved` | The organization of the withdrawal is unresolved |
| `409` | `duplicate_withdraw` | Duplicate withdrawal |
| `412` | `custody_not_enabled` | The `CUSTODY` connector of your organization is not enabled |
| `415` | `custody_portfolio_treasury_unsupported` | `portfolio` on your organization |
| `415` | `custody_treasury_withdraw_unsupported` | `withdraw` on your organization |
| `416` | `amount_precision_exceeded` | More decimals than the asset allows |
| `416` | `withdraw_below_minimum` | The EUR value of the quantity is below the minimum |
| `416` | `withdraw_price_unavailable` | No price is available to value the withdrawal |
| `416` | `requested_amount_error` | Insufficient balance |
| `416` | `custody_vault_insufficient` | The custody vault is insufficient |
| `416` | `withdraw_target_too_long` | `targetAddress` is too long |
| `422` | `withdraw_target_invalid` | `targetAddress` is not valid |
| `422` | `withdraw_target_tag_required` | The asset needs a `targetTag` |
| `422` | `withdraw_target_address_required` | `targetAddress` is missing |
| `422` | `invalid_idempotency_key` | `idempotencyKey` is not valid (64 characters max) |
| `422` | `asset_not_supported` | The custodian does not support this asset |
| `422` | `asset_address_unavailable` | No deposit address is available for this asset |
| `423` | `blocked_by_alert` | An active compliance alert blocks the customer |
| `423` | `custody_lock_unavailable` | The custody is locked by a concurrent operation |
| `503` | `custody_vault_unavailable` | The custodian's vault is unavailable |
| `503` | `custody_gas_unavailable` | Gas is unavailable at the custodian |
| `503` | `custody_address_unverifiable` | The destination address could not be verified |

## Related

- [Assets](../concepts.md#assets) — uuid or ISO code, case
- [Amounts](../concepts.md#amounts) — crypto amounts as strings, minimums
- [Assets catalogue](asset.md) — the decimals (`baseUnit`) and state of each asset
- [Trading](trading.md) — sales take crypto from custody
- [Staking](staking.md) — staking moves crypto from custody to the provider
- [Transactions](transaction.md) — the journal where deposits and withdrawals appear
- [Webhooks](webhooks.md) — `custody.wallet.created`, `custody.received`, `custody.sent`, `custody.transaction`
