# Connectors

A connector — a *core* — is a service the BITGEN platform is plugged into: a bank (`RAMP`), an exchange (`TRADING`), a custodian (`CUSTODY`), a staking provider (`STAKING`), an identity verification service (`IDENTITY`) or an anti-money-laundering service (`AML`). `$client->core` reads this catalogue: which connectors exist, their state, and — for staking providers — the asset and the offer they carry. Its main use for an integration is to pick a staking provider ([Staking](staking.md)).

Examples use `$client`, a configured `BitgenClient` ([Configuration](../configuration.md)).

## Methods

| Method | What it does | Returns |
|---|---|---|
| `list(...)` | Lists the connectors of the platform, optionally filtered by type, asset and state | `Page<Core>` |
| `get($core)` | Reads one connector | `Core` |

Models of this resource, under `Bitgen\Sdk\Model`: `Core`, `CoreConfigField`, `CoreConfigData`, `AssetRef` — and the constant classes `CoreType`, `CoreState`.

The catalogue is managed by BITGEN. Which connectors your organization uses, and their configuration, are set in the BITGEN interface — not through the API.

## List

```
$client->core->list(?string $type = null, string|Model\Asset|AssetRef|null $asset = null, ?string $state = null): Page<Core>
```

Not paginated: `count` is everything that matches. All the filters are optional and combine.

| Argument | Type | Description |
|---|---|---|
| `type` | `?string` | `CoreType::IDENTITY`, `AML`, `TRADING`, `CUSTODY`, `STAKING` or `RAMP` — anything else is refused before any request |
| `asset` | `string\|Model\Asset\|AssetRef\|null` | Only the connectors attached to this asset (the `STAKING` ones), by uuid, ISO code or model ([Assets](../concepts.md#assets)) — unknown → `404 unknown_asset` |
| `state` | `?string` | `CoreState::ENABLED` or `CoreState::DISABLED` — anything else is refused before any request |

```php
<?php

use Bitgen\Sdk\Asset;
use Bitgen\Sdk\Model\CoreType;

$banks = $client->core->list(type: CoreType::RAMP);
$exchanges = $client->core->list(type: CoreType::TRADING);
$custodians = $client->core->list(type: CoreType::CUSTODY);
$providers = $client->core->list(type: CoreType::STAKING, asset: Asset::ETH);   // the staking providers of ETH

foreach ($providers->items as $provider) {
    echo $provider->name, ' ', $provider->asset?->iso, ' ', $provider->state, PHP_EOL;   // bitgen_eth ETH ENABLED
}
```

Returns the matching `Core` connectors ([get](#get)).

## Get

```
$client->core->get(string|Core $core): Core
```

`$core` is the uuid of the connector, or a `Core`.

```php
<?php

$core = $client->core->get('CORE_UUID');

foreach ($core->config as $field) {
    echo $field->name, ' ', json_encode($field->data->value), PHP_EOL;   // min_deposit "1" …
}
```

Returns a `Core`:

| Property | Description |
|---|---|
| `uuid` | The connector |
| `state` | `CoreState::ENABLED` or `CoreState::DISABLED` — a string |
| `name` | The identifier of the connector — for a `STAKING` connector, `<provider>_<iso>` (`figment_sol`, `bitgen_eth`) |
| `label` | Display name |
| `type` | `CoreType::IDENTITY`, `AML`, `TRADING`, `CUSTODY`, `STAKING` or `RAMP` — a string |
| `asset` | `AssetRef` (`uuid`, `iso`, `label`) for a `STAKING` connector (derived from its `name`), `null` for the other types |
| `config` | The configuration schema of the connector, a list of `CoreConfigField`: `name` (key), `label` (display names by language, `['fr' => …, 'en' => …]`), `data` (`CoreConfigData`: `type` — `string`, `int`, `bool`, `password` or `webhook` — and `value`, the value, empty for secrets). The secrets of your organization's configuration never appear here. |

An unknown uuid answers `404 unknown_core`.

## Errors

In addition to the [common errors](../errors.md#common-errors):

| Status | `errorCode` | Meaning |
|---|---|---|
| `400` | `invalid_core_status` | `state` is neither `ENABLED` nor `DISABLED` |
| `404` | `unknown_asset` | `list`: unknown `asset` |
| `404` | `unknown_core` | `get`: unknown connector |
| `424` | `unknown_core_type` | `type` is not one of the connector types |

## Related

- [Staking](staking.md) — `$client->staking->providers()` is `list` filtered on `STAKING`
- [Assets](../concepts.md#assets) — uuid or ISO code, case
