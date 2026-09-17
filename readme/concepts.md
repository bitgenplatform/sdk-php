# Concepts

The conventions shared by every resource of the SDK: how a customer is designated, how amounts, pages, booleans, assets and dates travel, and which customers the financial resources accept. Examples use `$client`, a configured `BitgenClient` ([Configuration](configuration.md)).

## User references

Wherever a method expects a customer, its signature says `UserRef` — a documentation alias, not a class: in code the parameter is typed with the union `string|Created|Customer|Account|UserSummary|OrderUser`. A string is the customer's **uuid**; a model is one that carries it. The SDK sends the uuid of the model: the `Created` returned by `$client->customer->create()`, a `Customer` of `$client->customer->list()`, an `Account` of `$client->customer->get()`, the `user` of an `Order`, the `owner` of a `Transaction` or of a `StakingMovement`.

```php
<?php

$account = $client->bank->get('CUSTOMER_UUID');
$account = $client->bank->get($customer);   // the Created returned by $client->customer->create()
```

Any other object — a `Wallet`, an `Order`, a plain object with a `uuid` — is a type error at the call (`TypeError`), before any request. An email works too where the API resolves it (`customer`, `bank`, `custody`, `staking` — not `trading`, nor the `user` filter of the lists), but the uuid is cheaper for the API — prefer it.

The same goes for the other objects the SDK returns: wherever a method expects the uuid of an order, a movement, a position, a connector, a transaction, a subscription, an event of the catalogue or a key, it also takes the model itself (`$client->staking->rewards($movement->staking)`, `$client->trading->get($order)`), and sends its uuid.

## Amounts

The API handles crypto amounts as **strings** (up to 18 decimals): a PHP `float` only keeps about 15 significant digits and nothing on the server side restores what it lost. The SDK therefore:

- accepts a `string`, an `int` or a `float` and always sends it as a string — a string is sent as is, an `int` or a `float` in its shortest decimal form;
- refuses, with an `InvalidArgumentException` and before any request, an empty string, a negative or non-finite number, and a `float` PHP would write in exponent notation — below `0.0001` or from `1e21`: pass those as strings;
- never rounds or reformats a string: `'0.000000000000000001'` reaches the API untouched.

**Prefer strings**, even for EUR: `0.1 + 0.2` is `0.30000000000000004` as a `float`. In responses, crypto quantities are strings and EUR amounts are `float`s with 2 decimals.

**Minimums.** Purchases, sales, on-chain withdrawals and staking movements have minimums — set by BITGEN per environment, subject to change, never hard-coded in the SDK. The API's answer is the source of truth: `416 invalid_amount` for a purchase or a sale, `416 withdraw_below_minimum` for an on-chain withdrawal, `422 amount_below_minimum` for a staking movement. For staking, the provider's minimums are readable in its configuration (`$client->staking->providers()`, [Staking](resource/staking.md#providers)).

## Pagination

Paginated lists take `offset` and `limit` (`limit`: default 10, max 50; the transaction journal accepts up to 100) and return a `Bitgen\Sdk\Page`:

```php
<?php

$page = $client->asset->list();

$page->count;   // the total number of items
$page->items;   // the items of this page, typed models — Page<Asset> here
```

`Page<T>` is generic for PHPStan and Psalm: `$page->items` is a `list<T>`.

## Query booleans

The boolean filters of the lists (`includeClosed`, `includeRevoked`, `includeArchived`) are sent as `true` / `false`. The API also reads `1` / `0`, treats an absent parameter as `false`, and answers `422 invalid_<param>` for any other value: `invalid_include_closed`, `invalid_include_revoked`, `invalid_include_archived`.

## Assets

Wherever an asset is expected, the SDK accepts its **uuid** or its **ISO code** as a string, in any case (the API normalizes it) — or an `Asset` / `AssetRef` model returned by the SDK, whose uuid is then sent. The `Bitgen\Sdk\Asset` constants are the ISO codes of the main assets; any other code known to the catalogue ([Assets](resource/asset.md)) is passed as a plain string.

```php
<?php

use Bitgen\Sdk\Asset;

Asset::BTC;    // 'btc'
Asset::ETH;    // 'eth'
Asset::USDC;   // 'usdc'
Asset::XRP;    // 'xrp'
Asset::SOL;    // 'sol'
Asset::VALUES; // ['btc', 'eth', 'usdc', 'xrp', 'sol']

$eth = $client->asset->get(Asset::ETH);   // or by uuid
$wallet = $client->custody->wallet($customer, $eth);   // the model: its uuid is sent
```

The `iso` the API returns has the case it is stored with (`ETH` today): **compare it case-insensitively**. `Bitgen\Sdk\Asset` holds the ISO codes; the asset returned by `$client->asset` is the `Bitgen\Sdk\Model\Asset` model.

## Constants

The SDK has no PHP enums: every value the API enumerates is a **string constant** on a small class — `Env::SANDBOX` is `'sandbox'`, `Locale::FR` is `'FR'`, `TradingDirection::BUY` is `'buy'`, `AssetState::AVAILABLE` is `'AVAILABLE'`, `WebhookEventName::CUSTODY_SENT` is `'custody.sent'` — and each class lists its values in `VALUES`, in the order of the API.

```php
<?php

use Bitgen\Sdk\Asset;
use Bitgen\Sdk\Model\AssetState;
use Bitgen\Sdk\Model\Locale;

$eth = $client->asset->get(Asset::ETH);
if ($eth->state === AssetState::AVAILABLE) {   // outputs are strings: compare them with the constants
    $client->customer->update($customer, locale: Locale::EN);   // inputs take the constant
}
echo implode(', ', Locale::VALUES), PHP_EOL;   // FR, EN
```

An input that is not one of the values (`locale: 'en'`, `direction: 'Buy'`) is refused with an `InvalidArgumentException` before any request: the case matters. An output the SDK does not know yet (a state the API added) is kept as is, as a string.

## Timestamps and histories

Timestamps are **epochs in seconds** (`createdAt`, `updatedAt`, `date`, `expiresAt`…). Time series are a `Bitgen\Sdk\Model\History` with five lists of points, `d`, `w`, `m`, `y` and `all`:

```php
<?php

use Bitgen\Sdk\Asset;

$btc = $client->asset->ticker(Asset::BTC);

foreach ($btc->history->d as [$epoch, $price]) {   // the last 24 hours, one point per hour
    echo date('H:i', $epoch), ' ', $price, PHP_EOL;
}
```

Each point is `[epoch seconds, value]`: `d` covers the last 24 hours with one point per hour, `w` and `m` one point per day, `y` and `all` one point per month; the last point is the current value. Histories are the EUR price of an asset (`$client->asset`), the EUR balance of a bank account, the EUR value of a wallet or of a whole custody, and the capital and revenues of a staking portfolio.

## Activation and identity

By default, creating a customer sends them an activation email. Until they click it, the account stays `CREATED` and the **financial resources do not see it**: the bank answers `404 unknown_bank`, custody and staking `403 org_forbidden`, trading `403 user_not_in_scope`. Only the customer resource sees it (where the customer appears as `CREATED`). An organization that handles onboarding itself creates its customers with `needActivation: false` — usable right away, no BITGEN email — and `notify: false` for no BITGEN emails at all.

If your organization uses BITGEN's identity verification, the customer's identity (KYC for a person, KYB for a business) must be validated first — `412 owner_identity_not_validated` when reading the EUR account, `403 kyc_not_validated` on custody, trading and staking otherwise. An organization that verifies the identity of its customers by its own means has no such requirement. The verification itself is not part of the SDK; its state is the `state` of the customer's identity.
