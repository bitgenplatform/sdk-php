# Customers

A customer is an end user of your organization on the BITGEN platform: a person (KYC) or a business (KYB) with a login, an identity file, settings and — once activated and verified — an EUR account, custody wallets, orders and staking positions. `$client->customer` creates customers, lists them, and reads or updates an account.

Examples use `$client`, a configured `BitgenClient` ([Configuration](../configuration.md)), and `$customer`, the `Created` returned by `create()`. A customer is designated by a `UserRef`: their uuid, or a model carrying it — `Created`, `Customer`, `Account`… ([User references](../concepts.md#user-references)).

## Methods

| Method | What it does | Returns |
|---|---|---|
| `create(...)` | Creates a customer in your organization and sends them the activation email | `Created` |
| `list(...)` | Lists the customers of your organization | `Page<Customer>` |
| `get($user)` | Reads one account: identity files, details, settings | `Account` |
| `update($user, ...)` | Updates the settings a key may write: theme, locale, notifications | `void` |

Models of this resource, under `Bitgen\Sdk\Model`: `Created`, `Customer`, `CustomerAccount`, `AccountAddress`, `CustomerClient`, `CustomerAction`, `CustomerSetup`, `CustomerCollaborations`, `CollaboratorLink`, `ManagerLink`, `CustomerAlert`, `CustomerBusiness`, `Account`, `AccountNotifications`, `Identity` (`KycIdentity`, `KybIdentity`), `KycIdentityForm`, `KybIdentityForm`, `IdentityData`, `IdentityStep` — and the constant classes `CustomerState`, `IdentityState`, `IdentityMode`, `Locale`, `OrganizationCategory`.

## create

```
$client->customer->create(string $email, string $manager, ?string $firstname = null, ?string $lastname = null, ?string $fin = null, ?bool $needActivation = null, ?bool $notify = null, ?string $locale = null, ?string $organization = null): Created
```

| Argument | Type | Description |
|---|---|---|
| `email` | `string` | Login of the customer — required |
| `manager` | `string` | uuid of the collaborator of your organization who follows this customer — required; the customer is created in your organization |
| `firstname`, `lastname` | `?string` | Optional |
| `fin` | `?string` | Tax identification number of the customer — optional, 100 characters max |
| `needActivation` | `?bool` | Default `true`: BITGEN emails the customer an activation link and the account stays `CREATED` (the bank, custody, trading and staking resources do not accept it) until they activate. `false`: the account is usable right away and BITGEN sends no email — for an organization that handles activation and notifications with its own system, or through webhooks |
| `notify` | `?bool` | Default `true`: the customer receives BITGEN's emails (newsletter). `false`: none |
| `locale` | `?string` | `Locale::FR` (default) or `Locale::EN` — anything else is refused before any request |
| `organization` | `?string` | Category: `OrganizationCategory::CUSTOMER` (default) or `OrganizationCategory::B2B` — `B2B` also opens a KYB file. `BUSINESS` is reserved to BITGEN administrators: like any other value, the SDK refuses it before any request |

```php
<?php

use Bitgen\Sdk\Model\Locale;
use Bitgen\Sdk\Model\OrganizationCategory;

$customer = $client->customer->create(
    email: 'jean@valjean.fr',
    manager: 'MANAGER_UUID',
    firstname: 'Jean',
    lastname: 'Valjean',
    locale: Locale::FR,
    organization: OrganizationCategory::B2B,   // a business: a KYB file is opened too — CUSTOMER by default
);

echo $customer->uuid, PHP_EOL;
```

By default the API creates the account and sends the customer an activation email. Until they click it, the account stays `CREATED` (`setup->needActivation` is `true`) and the bank, custody, trading and staking resources do not accept it — only `list`, where it appears as `CREATED`, and `get` see it ([Activation and identity](../concepts.md#activation-and-identity)).

```php
<?php

$customer = $client->customer->create(
    email: 'jean@valjean.fr',
    manager: 'MANAGER_UUID',
    needActivation: false,   // you handle onboarding yourself
    notify: false,
);
```

With `needActivation: false` the account is usable right away and BITGEN sends no activation email — for an organization that handles activation and notifications with its own system, or through webhooks; with `notify: false` the customer receives no BITGEN email at all.

When the email already belongs to an active account whose KYC is validated, that account is **attached** to your organization instead of being created. An active account without a validated KYC cannot be attached (`412 user_not_attachable`), an account already attached to another organization is refused (`409 user_already_assigned`), and so is an account still being created, for 15 minutes (`409 account_unavailable`).

Returns a `Created`: the `uuid` of the customer — pass it as is to the other resources.

## list

```
$client->customer->list(?int $offset = null, ?int $limit = null, ?bool $includeClosed = null, ?string $manager = null): Page<Customer>
```

| Argument | Type | Description |
|---|---|---|
| `offset`, `limit` | `?int` | [Pagination](../concepts.md#pagination) |
| `includeClosed` | `?bool` | Also returns the `CLOSED` customers — default `false` ([Query booleans](../concepts.md#query-booleans)) |
| `manager` | `?string` | Only the customers whose direct manager is this collaborator (uuid) |

```php
<?php

$page = $client->customer->list(offset: 0, limit: 50);

foreach ($page->items as $item) {
    echo $item->login, ' ', $item->state, PHP_EOL;   // jean@valjean.fr ENABLED
}
```

Returns a page of `Customer`:

| Property | Description |
|---|---|
| `uuid`, `createdAt` | Identifier and creation time (epoch seconds) |
| `state` | `CustomerState::CREATED` (activation pending), `ENABLED`, `CLOSED` or `FROZEN` — a string; the `CustomerState` constants name the known values |
| `isAvailable` | `false` until the customer has activated their account, then `true` |
| `canLogin` | Whether the customer may sign in to the BITGEN web application |
| `login` | The email |
| `account` | `CustomerAccount`: `email`, `firstname`, `lastname`, `fin` (tax identification number), `birthdate` (date of birth, epoch seconds, or `null`), `phoneZone` and `phoneNumber` (dialing code, `+33` by default, and the number as an integer), `address` (`AccountAddress`: `uuid`, `address`, `state` — the state of the address record — or `null`), `referralCode` (the customer's own referral code, generated at creation) |
| `client` | `CustomerClient`: `roles` (platform roles of the account — always `ROLE_USER` for a customer), `hasTfa` (two-factor authentication enabled), `hasPhishing` (anti-phishing code enabled), `isValid` (`true` once the account has been activated) |
| `action->setup` | `CustomerSetup`: `theme` (theme of the BITGEN web application, `light` by default), `currency` (display currency, `EUR`), `locale` (language of the web application and of the emails: `Locale::FR` or `Locale::EN`), `choosenOrganization` (category chosen at signup: `OrganizationCategory::CUSTOMER`, `B2B` — a string, compare it with the constants), `needActivation` (activation email pending), `notify` (whether the customer accepts BITGEN emails), `onboarding` (whether the web onboarding has been completed) |
| `identity` | The KYC or KYB file of the customer ([Identity](#identity)) |
| `business` | The businesses of the customer, each with its KYB file: a list of `CustomerBusiness` (`identity`) |
| `collaborations->collaborator` | The customer's attachment to your organization, a list of `CollaboratorLink`: `uuid`, `state` (`WAIT` until activation, then `ENABLED`; `REVOKED` once removed), `roles` (`ROLE_USER` for a customer), `organization` (its name), `organizationUuid`, `manager` (uuid of the collaborator in charge of them) |
| `collaborations->manager` | Attachments where this account manages other people — always empty for a customer (a list of `ManagerLink`: `mandate`, `mandatedUntil`: CRM data, not needed for an integration) |
| `alert` | Active compliance alerts, a list of `CustomerAlert`: `uuid`, `state` (`OPEN`, `DECLARATED`, `CONFIRMED`), `severity` (`SUCCESS`, `WARNING`, `CRITICAL`), `sources` (the observations behind the alert — analysis data) |

## get

```
$client->customer->get(UserRef $user): Account
```

`$user` is the customer, by uuid or by model ([User references](../concepts.md#user-references)).

```php
<?php

$account = $client->customer->get($customer);

echo $account->setup->needActivation ? 'activation pending' : 'activated', PHP_EOL;
echo $account->identity->state, PHP_EOL;   // VALIDATED once the verification is done
```

Returns an `Account`:

| Property | Description |
|---|---|
| `uuid` | The customer |
| `identity` | The KYC or KYB file of the customer ([Identity](#identity)) |
| `business` | The businesses of the customer, each with its KYB file: a list of `CustomerBusiness` |
| `account` | The same `CustomerAccount` as `Customer`, with `address` reduced to `uuid` and `address` (`state` is `null`) |
| `notifications` | `AccountNotifications` — email preferences: `login` (login-related emails), `newsletter` (BITGEN newsletter) |
| `setup` | The same `CustomerSetup` as `Customer->action->setup`: theme, display currency, language, category chosen at signup, activation pending, BITGEN emails accepted, onboarding completed |

An unknown customer answers `404 unknown_user`.

### Identity

`Identity` is the verification file of a person (`mode` `KYC`) or of a business (`KYB`). The verification itself — questionnaire, documents — is not part of this SDK: read its progress here. Whether a validated identity is required before the financial resources depends on your organization ([Activation and identity](../concepts.md#activation-and-identity)).

| Property | Description |
|---|---|
| `uuid` | The file |
| `state` | `IdentityState::CREATED`, `IN_PROGRESS`, `WAIT`, `PENDING`, `VALIDATED`, `REJECTED`, `FROZEN`, `EXPIRED` or `CLOSED` — a string; the `IdentityState` constants name the known values |
| `mode` | `IdentityMode::KYC` or `IdentityMode::KYB` — a string |
| `form` | On a `KycIdentity`, a `KycIdentityForm` — the answers of the KYC questionnaire: `european_residency` (boolean), `ppe` (politically exposed person, boolean), `ppp` (relative of a politically exposed person, boolean), `source_income`, `net_income`, `experience` (crypto experience); plus `score` (internal scoring) and `submittedAt`. On a `KybIdentity`, a `KybIdentityForm`: `activity` (business activity), `score`, `submittedAt` |
| `data->steps` | One `IdentityStep` per step, `status` and `submittedAt` — KYC: `info`, `selfie`, `identity`, `residency`; KYB: `info`, `kbis`, `status`, `domiciliation`, `rbe` |
| `data->verificationUrl` | URL of the identity verification when the provider hosts it, `null` otherwise |
| `data->hosted` | Whether the verification is hosted by the provider (`null` when the API does not say) |
| `data->notifications` | Internal flag |
| `validatedAt`, `expiresAt` | Epoch seconds, or `null` |
| `renewalNotifiedAt` | When the renewal reminder was sent (epoch seconds, or `null`) — an identity expires after 12 months (6 for a politically exposed person) |

`Identity` is a class hierarchy: a `KycIdentity` or a `KybIdentity`, each with its typed `form` — narrow with `instanceof`. A `mode` the SDK does not know yet gives a plain `Identity`, with the common fields only.

```php
<?php

use Bitgen\Sdk\Model\KybIdentity;
use Bitgen\Sdk\Model\KycIdentity;

$identity = $client->customer->get($customer)->identity;

if ($identity instanceof KycIdentity) {
    echo $identity->form->source_income, PHP_EOL;   // salary
} elseif ($identity instanceof KybIdentity) {
    echo $identity->form->activity, PHP_EOL;
}
```

## update

```
$client->customer->update(UserRef $user, ?string $theme = null, ?string $locale = null, ?array $notifications = null): void
```

`$user` is the customer, by uuid or by model.

| Argument | Type | Description |
|---|---|---|
| `theme` | `?string` | Theme of the BITGEN web application (`light` by default) |
| `locale` | `?string` | Language of the web application and of the emails: `Locale::FR` or `Locale::EN` — anything else is refused before any request |
| `notifications` | `?array` | Email preferences, `['login' => bool, 'newsletter' => bool]` — `login` (login-related emails), `newsletter` (BITGEN newsletter) |

```php
<?php

use Bitgen\Sdk\Model\Locale;

$client->customer->update($customer, locale: Locale::EN, notifications: ['login' => true, 'newsletter' => false]);
```

These are the only settings an API key may write — the account details, the identity state and the address are not — and the SDK sends nothing else: an argument left to `null` is not sent. The API answers with an empty body.

## Errors

In addition to the [common errors](../errors.md#common-errors):

| Status | `errorCode` | Meaning |
|---|---|---|
| `400` | `invalid_fin` | `fin` (tax identification number) is longer than 100 characters, or not a scalar |
| `403` | `missing_group_organization_or_manager` | `manager` is missing |
| `404` | `unknown_user` | Unknown customer (`get`), or unknown `manager` (`list`) |
| `409` | `user_already_assigned` | The email belongs to an account attached to another organization |
| `409` | `account_unavailable` | The email belongs to an account still being created (less than 15 minutes ago) |
| `412` | `user_not_attachable` | The email belongs to an active account without a validated KYC |
| `422` | `invalid_include_closed` | `includeClosed` is not a boolean value |

## Related

- [User references](../concepts.md#user-references) — uuid or an object with a `uuid`
- [Activation and identity](../concepts.md#activation-and-identity) — what a customer can do before and after activation, and when a validated identity is required
- [Bank accounts](bank.md) — the EUR account of a customer
- [Custody wallets](custody.md) — their crypto wallets
- [Webhooks](webhooks.md) — `user.created` and the `user.identity.*` events
