# Contributing

Internal notes for working on `bitgen/sdk`. The client documentation lives in `README.md` and `readme/`; this file is not published.

## Prerequisites

Nothing installed locally: everything runs in throwaway containers mounted on the repository.

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 install                 # dependencies (composer.lock is committed)
docker run --rm -v "$PWD":/app -w /app php:8.2-cli vendor/bin/phpstan analyse --no-progress
docker run --rm -v "$PWD":/app -w /app php:8.2-cli vendor/bin/phpunit    # same with php:8.3-cli and php:8.4-cli
docker run --rm -v "$PWD":/app -w /app composer:2 validate --strict
```

No network access is needed once `composer install` has run: the tests work offline.

## Commands

```bash
composer validate --strict
composer analyse    # PHPStan, level max, src/ and tests/
composer test       # PHPUnit
```

`composer test` runs the HTTP layer against a fake transport, the curl transport against a throwaway `php -S` server (`tests/server/router.php`: timeout, closed port, redirect, JSON and non-JSON answers), the resources against the fake transport, the argument validation, and the documentation (`tests/DocsTest.php`: every ```` ```php ```` block of `README.md` and `readme/**/*.md` is executed against the package with `$client` pointed at `tests/server/docs.php`, every relative link and anchor must resolve, the `README.md` title must carry `Version::VERSION`, and no API route or path may appear in the published documentation — `testNoApiRouteInThePublishedDocumentation`, with an explicit allow-list for data values that look like one). When the output of `phpunit` is piped, put `timeout` in front of it: a test server left behind would keep the pipe open.

## Repository layout

- `src/BitgenClient.php` — the entry point: named-argument constructor, validation, one read-only property per resource
- `src/Env.php`, `src/Asset.php` — string constants (no PHP enum in the SDK: `final class` + `public const` + `VALUES`); `src/Version.php` — the version (User-Agent, README title)
- `src/Http/` — `HttpClient` (URL, query, headers, JSON, error mapping), `Transport` + `CurlTransport` (the wire, `@internal`), `BaseUrl`, `Timeout`, `Response`, `TransportException`
- `src/Support/` — argument validation: `Path` (path segments), `UserId` (customers — defines the `UserRef` PHPDoc type: `string|Created|Customer|Account|UserSummary|OrderUser`), `AssetId` (assets: string, `Asset` or `AssetRef` model), `Amount` (amounts), `Enum` (`ensure()`: a string against a `VALUES` list, static message)
- `src/Model/` — one `final readonly` class per object the API returns (`fromArray`; `Identity` is the `readonly` base of `KycIdentity` / `KybIdentity`; `TravelRule` the abstract base of `TravelRulePerson` / `TravelRulePlatform`, built by the integrator, `toArray()` for the request body), the constant classes of the values the API lists (`VALUES`, no enum), `Cast` (typed reads, `answer` / `answerList` for the top-level shape, `@internal`), `History`; `src/Resource/` — one class per resource (not `final`: integrators mock them), hung off `BitgenClient` (`StakingResource` also takes the `CoreResource`, for `providers()`; `WebhooksResource::verify()` is the one method that sends nothing — HMAC on the raw bytes, `hash_equals`, freshness, envelope — tested by `tests/Resource/WebhooksVerifyTest.php`)
- `src/Page.php` — generic paginated list; `src/Exception/BitgenException.php`
- `tests/` — PHPUnit, mirrors `src/`; `tests/Http/FakeTransport.php` records requests and answers with queued responses; `tests/server/router.php` is the throwaway HTTP server of the transport tests, `tests/server/docs.php` the documentation server the examples run against (realistic answers, route by route — extend it with every resource)
- `README.md`, `readme/` — client documentation, published with the package; `CHANGELOG.md`

## Adding a resource

1. Read the API reference of the resource (ask BITGEN's API team). Write its models under `src/Model/` (one `final readonly` class per object the API returns, `fromArray(array $data): self` reading through `Cast`, fields typed as the API documents them — every `state` a plain string —, unknown fields ignored), then `src/Resource/<Resource>.php` — named scalar arguments defaulting to `null` (not sent), input strings validated against the `VALUES` of their constant class before any request (`Support\Enum::ensure`), path segments through `Path::segment`, customers through `UserId::resolve` (the `UserRef` union), assets through `AssetId::resolve`, amounts through `Amount::normalize`, explicit request bodies, answers through `Cast::answer` (an object), `Cast::answerList` (a bare array) or `Page::fromArray`, `@throws` on every public method — and its read-only property on `BitgenClient`.
2. Write `tests/Resource/<Resource>Test.php` with the fake transport: for every method, the exact path, query and body sent, the mapping of the answer, and at least one error (`403 forbidden_permission` at minimum).
3. Document it: `readme/resource/<resource>.md` on the template of the Node.js SDK (introduction, methods, one section per method, errors, related), its line in `README.md`, `CHANGELOG.md` — then re-read `README.md` and `readme/` in full.
4. Run `composer validate --strict`, PHPStan and PHPUnit on PHP 8.2, 8.3 and 8.4.

## Release

`bitgen/sdk` exists on Packagist (the 0.1.x versions are there) with the GitHub hook in place: every `vX.Y.Z` tag pushed to `bitgenplatform/sdk-php` is published automatically — there is no publication workflow in this repository. The archive Packagist serves is `git archive` of the tag: `.gitattributes` keeps it to `src/`, `readme/`, `README.md`, `CHANGELOG.md` and `composer.json` (`export-ignore` on the tests, the CI, the tool configurations, this file and the lock).

1. Set `Version::VERSION`, the `README.md` title (`# bitgen/sdk — vX.Y.Z`, checked by `tests/DocsTest.php`) and the date of the `CHANGELOG.md` entry — the three always move together.
2. Commit on `main`, then `git tag -a vX.Y.Z -m vX.Y.Z` and `git push origin main vX.Y.Z` — `ci.yml` must be green on the commit.
3. Check the version on [packagist.org/packages/bitgen/sdk](https://packagist.org/packages/bitgen/sdk) (a few minutes after the push), then install it in a blank container — it must print the version:

   ```bash
   docker run --rm -v "$PWD":/app -w /app composer:2 sh -c "mkdir /t && cd /t && composer require bitgen/sdk:^1.0 --no-interaction && php -r 'require \"vendor/autoload.php\"; echo \\Bitgen\\Sdk\\Version::VERSION, PHP_EOL;'"
   ```

## Rules

- The BITGEN API v4 is the only source of truth: routes, fields, error codes and behaviours come from the API reference kept by BITGEN's API team (not part of this repository), nothing is invented. A point it does not cover is a question to the API team, not a guess.
- `README.md` and `readme/` are re-read in full on every change: nothing stale, nothing anticipated, nothing the API does not do; every PHP example runs and every link resolves (`tests/DocsTest.php`). The documentation never lists the permissions of a key (they are set by the platform, not by the integrator), nor API routes or paths: it documents the SDK, not the API — the errors an integrator receives stay.
- No dependency beyond `ext-curl` and `ext-json`; PHP `^8.2` syntax only.
- Nothing ever contains the API key: not a URL, not an exception message, not a log.
