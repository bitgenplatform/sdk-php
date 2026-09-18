## [1.0.2] - 2026-09-18

### Changed
- Documentation only, no code change. Ten diagrams in `readme/media/`: the states of a purchase and of a sale, the activation and identity of a customer, the receiving of a webhook delivery, the movement and the position of a staking, the lifecycle of a transaction, the path of a purchase, of a sale, of an EUR deposit and of an EUR withdrawal
- `readme/concepts.md`: two new sections, `Following a purchase and a sale` and `Following a deposit and a withdrawal` — where the money goes, resource by resource, and what your organization reads afterwards
- Resource pages aligned with the platform's behaviour: `trading` — from which states an order reaches `FAILED` or `PARKED`, and what happens to the money; `staking` — one movement per position, rewritten `UNSTAKE` on a full exit, the cycle of the position, what `rewards` and `unstake` change, the events of the operations journal (`validated`, `failed`, `canceled`, `reward`, `claim`, `closed`) and their `movement`; `transaction` — the lifecycle of a transaction and `credited` for incoming transactions; `bank` — the bank provider holds the funds and BITGEN keeps the ledger, a declared deposit is credited after processing and compliance analysis, a withdrawal is reserved then debited at the confirmation of the wire, `pending.in` and `pending.out`
- Section titles of the resource pages start with a capital letter (`## Get`, `## Withdraw`, `## Credit`…); anchors are unchanged

## [1.0.1] - 2026-09-17

### Added
- `OrganizationCategory` (`CUSTOMER`, `B2B`), `ApikeyState` (`ENABLED`, `REVOKED`) and `SubscriberState` (`ENABLED`, `ARCHIVED`) constants under `Bitgen\Sdk\Model` — the `$organization` of `CustomerResource::create()` is validated against `OrganizationCategory::VALUES` like every other enumerated argument

## [1.0.0] - 2026-09-17

### Breaking
- Targets the BITGEN API v4: no more `/api/v3` prefix, real HTTP status codes
- Guzzle is gone: the SDK only needs `ext-curl` and `ext-json`
- `BitgenClient` takes named arguments (`scope`, `apiKey`, `env`, `host`, `port`, `isSsl`, `timeout`) instead of an array; `env` is one of the `Bitgen\Sdk\Env` constants
- `BitgenException` exposes `$status` (the HTTP status) and `$errorCode` (the stable error code, a string); `$service`, `$module` and `$apiMessage` are removed; `getMessage()` is `"<errorCode> (HTTP <status>)"` and `getCode()` the status
- Invalid arguments throw an `InvalidArgumentException` before any request
- Environments: `sandbox` now targets `https://api.sandbox.bitgen.com` (`https://api.staging.btgn.dev` is `Env::STAGING`), `localhost` defaults to port `3002` (was `14303`); custom `host` / `port` / `isSsl` unchanged
- Requires PHP 8.2 or later
- The 0.1.x models and resources are removed

### Added
- `Env` (`PRODUCTION`, `SANDBOX`) and `Asset` (`BTC`, `ETH`, `USDC`, `XRP`, `SOL` — provisional list) string constants — no PHP enum anywhere: every enumerated value is a constant on a class listing its `VALUES`
- Every method that expects the uuid of an object the SDK returns also takes the model itself (`UserRef` for customers: `Created`, `Customer`, `Account`, `UserSummary`, `OrderUser`; `Order`, `StakingMovement`, `StakingPosition`, `Core`, `Transaction`, `Subscriber`, `WebhookType`, `Apikey`; `Asset` / `AssetRef` for assets)
- `timeout` option, in seconds (default `30`, `0` disables)
- `request_timeout` / `network_error` errors (`status` `0`, transport error in `getPrevious()`)
- `User-Agent: bitgen-sdk-php/<version>` header on every request
- `Page<T>` for paginated lists (`count`, `items`)
- `customer` resource (`create` with the `needActivation` / `notify` options, `list`, `get`, `update`) and its models — `Identity` as `KycIdentity` / `KybIdentity`
- `bank` resource (`get`, `operations`, `withdraw`, `credit`) and its models
- `custody` resource (`wallets`, `wallet`, `portfolio`, `withdraw` with `TravelRulePerson` / `TravelRulePlatform`) and its models
- `trading` resource (`buy`, `sell`, `get`, `list`) and its models
- `transaction` resource (`list`, `get`) and its models
- `staking` resource (`providers`, `stake`, `list`, `movements`, `get`, `rewards`, `unstake`, `operations`, `portfolio`) and its models
- `core` resource (`list`, `get`) and its models
- `webhooks` resource (`activate`, `updateEndpoint`, `regenerate`, `list`, `subscribe`, `archive`, `reactivate`, `logs`, `catalog`, `catalogItem`) and its models, with `verify()` to check a received delivery (HMAC signature, freshness, envelope)
- `apikeys` resource (`list`, `get`, `logs`) and its models
- `asset` resource (`list`, `get`, `tickers`, `ticker`) and its models

## [0.1.6] - 2026-03-22

### Changed
- Sandbox URL updated from `api.btgn.dev` to `api.staging.btgn.dev`
- Localhost default port changed from `80` to `14303`
- Added `isSsl` config option (default `true`) for custom host mode

### Fixed
- `isSsl` config option now correctly forwarded from `BitgenClient` to `Config`

## [0.1.5] - 2026-03-11

### Changed
- `BitgenRawException` removed — HTTP 500/400 errors from `POST /api/v3/tx` are now wrapped as a standard `BitgenException`
- All methods accepting a user now support `string|UserFull|UserListItem` in addition to a raw UUID string
- Added `Support/UserRef` for user reference resolution
- `Config` now supports 3 standard envs (`sandbox`, `production`, `localhost`) and a custom host mode (env ignored, HTTP, default port 80)

### Breaking
- `BitgenRawException` no longer exists — replace any `catch (BitgenRawException $e)` with `catch (BitgenException $e)`