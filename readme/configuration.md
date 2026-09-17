# Configuration

A `BitgenClient` is built once per API key and reused: it holds the credentials, the target environment and the request timeout. Its constructor takes named arguments.

```php
<?php

use Bitgen\Sdk\BitgenClient;
use Bitgen\Sdk\Env;

$client = new BitgenClient(
    scope: 'YOUR_SCOPE_UUID',
    apiKey: 'YOUR_API_KEY',
    env: Env::PRODUCTION,   // default
    timeout: 30,            // seconds, default 30
);
```

## Credentials

| Argument | Description |
|---|---|
| `scope` | uuid of the organization that owns the key. Sent as the `BITGEN-Scope` header. It is also the organization the SDK uses wherever the API expects yours. |
| `apiKey` | The raw key, shown once when it is created. Sent as the `Api-key` header. |

A missing key, an unknown, revoked or expired key, or a `scope` that is not the key's organization, is refused with a `401` ([Common errors](errors.md#common-errors)).

## Environments

| `env` | Constant | URL |
|---|---|---|
| `production` | `Env::PRODUCTION` (default) | `https://api.bitgen.com` |
| `sandbox` | `Env::SANDBOX` | `https://api.sandbox.bitgen.com` |

`Env` holds these names as constants (`Env::VALUES` lists them): pass the constant. Anything else is refused before any request ([Validation](#validation), [Constants](concepts.md#constants)).

## Custom host

To reach the API through another hostname — a container, a tunnel — give `host` instead of `env`:

```php
<?php

use Bitgen\Sdk\BitgenClient;

$client = new BitgenClient(
    scope: 'YOUR_SCOPE_UUID',
    apiKey: 'YOUR_API_KEY',
    host: 'my-hostname',   // bare hostname: no scheme, port or path
    port: 8080,            // default 80
    isSsl: false,          // default true (https)
);
```

## Timeout

`timeout` is the maximum time, in seconds, the SDK waits for the API to answer: `30` by default, `0` disables it; an `int` or a `float` (`0.5`). When it expires, the call throws a `BitgenException` with `status` `0` and `errorCode` `request_timeout` ([No HTTP response](errors.md#no-http-response)).

## Requests

Every request carries the headers `BITGEN-Scope`, `Api-key`, `Content-Type: application/json`, `Accept: application/json` and `User-Agent: bitgen-sdk-php/<version>`, where `<version>` is the installed version of the SDK. Redirects are never followed.

## Validation

An invalid configuration throws an `InvalidArgumentException` from the constructor, before any request is sent: empty `scope` or `apiKey` (or one that is not printable ASCII), `env` that is not one of `Env::VALUES`, `host` that is not a bare hostname, `port` outside 1–65535, `timeout` that is not a number of seconds between `0` and `2147483`. The values of `scope` and `apiKey` never appear in the message. Every other invalid argument is refused the same way, by the method that receives it ([Invalid arguments](errors.md#invalid-arguments)).

## Options

| Argument | Type | Default | Description |
|---|---|---|---|
| `scope` | `string` | — | Organization uuid |
| `apiKey` | `string` | — | API key |
| `env` | `string` | `Env::PRODUCTION` | Target environment — an `Env` constant |
| `host` | `?string` | `null` | Custom hostname, used instead of `env` |
| `port` | `?int` | `80` | Port, with `host` |
| `isSsl` | `bool` | `true` | `https` or `http`, with `host` |
| `timeout` | `int\|float` | `30` | Request timeout in seconds, `0` = none |
