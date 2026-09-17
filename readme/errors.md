# Errors

The API answers with real HTTP status codes and, on failure, a JSON body `{ error: true, message: '<code>', code: <status> }` where `message` is a stable snake_case code (`invalid_amount`, `unknown_asset`…), never a sentence. The SDK turns every non-2xx answer — and every request that gets no HTTP answer at all — into a `BitgenException`.

## BitgenException

`Bitgen\Sdk\Exception\BitgenException` extends `RuntimeException`:

| Member | Type | Description |
|---|---|---|
| `$status` | `int` | The HTTP status (`416`…), or `0` when no HTTP response was received |
| `$errorCode` | `string` | The stable code of the API (`requested_amount_error`), or the raw response text when the body is not the API's JSON error |
| `getMessage()` | `string` | `"<errorCode> (HTTP <status>)"` |
| `getCode()` | `int` | The same as `$status` |
| `getPrevious()` | `?Throwable` | The transport error, for `request_timeout` and `network_error` — `null` otherwise |

```php
<?php

use Bitgen\Sdk\Exception\BitgenException;

try {
    // any call of the SDK
} catch (BitgenException $e) {
    $e->status;         // 416
    $e->errorCode;      // 'requested_amount_error'
    $e->getMessage();   // 'requested_amount_error (HTTP 416)'
}
```

Nothing in the exception ever contains your API key: not the message, not the previous error.

## No HTTP response

| Status | `errorCode` | Meaning |
|---|---|---|
| `0` | `request_timeout` | No response within the configured `timeout` — 30 seconds by default ([Timeout](configuration.md#timeout)) |
| `0` | `network_error` | The request never got an HTTP answer: DNS, connection refused, TLS… `getPrevious()` holds the transport error |

## Webhook verification

`$client->webhooks->verify()` checks a delivery received by your endpoint locally, without any request: a delivery that fails the verification throws a `BitgenException` with `status` `0` and one of these codes ([Webhooks › verify](resource/webhooks.md#verify)):

| Status | `errorCode` | Meaning |
|---|---|---|
| `0` | `missing_signature` | No `X-BITGEN-Signature` header |
| `0` | `invalid_signature` | The signature does not match the body and the secret |
| `0` | `missing_timestamp` | No `X-BITGEN-Timestamp` header, or not a number |
| `0` | `timestamp_expired` | The timestamp of the delivery is more than `tolerance` seconds away from now (300 by default) |
| `0` | `invalid_payload` | The body is not the JSON envelope `{ delivery_id, timestamp, event, data }` |

## Unexpected answers

When the body is not the API's JSON error payload (proxy error page, unexpected `500`…), `errorCode` holds the raw response text, truncated to 200 characters; the same goes for a 2xx answer that is not JSON. A `500` is not always a failure of the API: it is also its answer to a request it does not recognize — with a custom `host`, check that it reaches the API unchanged. Redirects are never followed: a `3xx` answer is reported as a `BitgenException`, and the key is never replayed to another host. A 2xx answer that is JSON but not the shape the SDK expects (not an object, not a list of objects where the API promises one, a page whose `items` is not a list…) throws an `UnexpectedValueException`: not an error of the API, a contract violation to report to BITGEN. A value the SDK enumerates (a `state`, a `mode`…) is never checked: the API may add one, the models keep it as a string.

## Invalid arguments

An invalid argument — an empty `scope` or `apiKey`, a `host` with a scheme, a negative `timeout`, a value outside a constant list such as `locale: 'en'` ([Constants](concepts.md#constants))… — throws an `InvalidArgumentException` **before any request is sent**, from the constructor or from the method that receives it ([Validation](configuration.md#validation)).

## Common errors

The errors any call can answer.

| Status | `errorCode` | Meaning |
|---|---|---|
| `401` | `auth_missing` | The `BITGEN-Scope` or `Api-key` header is missing |
| `401` | `api_key_missmatch` | The key is unknown, revoked or expired, or `scope` is not the key's organization |
| `403` | `unknown_organization` | The organization of the key is unknown |
| `403` | `organization_not_enabled` | The organization is not enabled |
| `403` | `api_disabled` | API access is not enabled for the organization |
| `403` | `invalid_api_key` | The key is invalid |
| `403` | `expired_api_key` | The key has expired |
| `403` | `forbidden_permission` | The key does not carry the permission for this call — contact BITGEN |
| `400` | `required_index_missing::<field>` | A mandatory field is missing or empty |
| `404` | `unknown_<resource>` | The target does not exist — `unknown_user`, `unknown_asset`, `unknown_bank`… — or the API does not reveal it |
| `422` | `invalid_<param>` | A boolean filter (`includeClosed`, `includeRevoked`, `includeArchived`) is not a boolean value |
| `423` | `blocked_by_alert` | The customer is under an active compliance alert |
