# Installation

`bitgen/sdk` is the official PHP SDK for the BITGEN API v4. It runs server-side only: the API accepts browser requests from a fixed list of origins, so the SDK is not meant to be used from a browser.

## Requirements

- PHP 8.2 or later, with the `curl` and `json` extensions
- No other dependency: the SDK only uses what PHP already provides

## Install

```bash
composer require bitgen/sdk
```

## Import

Everything lives under the `Bitgen\Sdk` namespace and is autoloaded by Composer:

```php
<?php

use Bitgen\Sdk\BitgenClient;
use Bitgen\Sdk\Exception\BitgenException;
use Bitgen\Sdk\Env;
use Bitgen\Sdk\Asset;
```

The package exposes the client (`BitgenClient`), its exception (`BitgenException`), two constant classes — `Env`, the environments, and `Asset`, the ISO codes of the main assets ([Configuration](configuration.md), [Assets](concepts.md#assets)) — and, under `Bitgen\Sdk\Model`, the models the resources return, the constants naming their known values ([Constants](concepts.md#constants)), and the few objects a call takes as input (`TravelRulePerson`, `TravelRulePlatform`).

## Static analysis

The SDK is fully typed (`declare(strict_types=1)`, PHPStan level max): paginated lists are a generic `Page<T>` (`$page->count`, `$page->items`), and every value the API returns is a read-only object with typed properties.

## Next steps

- [Quick start](quick-start.md) — create the client and run a first customer journey
- [Configuration](configuration.md) — credentials, environments, custom host, timeout
- [Errors](errors.md) — what a failed call throws, and what is checked before any request
