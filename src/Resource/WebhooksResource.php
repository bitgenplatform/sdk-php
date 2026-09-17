<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Resource;

use Bitgen\Sdk\Exception\BitgenException;
use Bitgen\Sdk\Http\HttpClient;
use Bitgen\Sdk\Model\Cast;
use Bitgen\Sdk\Model\Created;
use Bitgen\Sdk\Model\DeliveryLog;
use Bitgen\Sdk\Model\Subscriber;
use Bitgen\Sdk\Model\WebhookEvent;
use Bitgen\Sdk\Model\WebhookSubscriptions;
use Bitgen\Sdk\Model\WebhookType;
use Bitgen\Sdk\Page;
use Bitgen\Sdk\Support\Path;
use InvalidArgumentException;
use JsonException;

/**
 * `/webhook`, `/webhooks` — the deliveries of the events of the organization to an endpoint of the integrator
 * (`$client->webhooks`). Wherever the API expects `{organization}`, the SDK sends the key's scope.
 * `verify()` checks a delivery received by the endpoint, locally, without any request.
 */
class WebhooksResource
{
    /** Default freshness window of `verify()`, in seconds */
    public const DEFAULT_TOLERANCE = 300;

    public function __construct(private readonly HttpClient $http)
    {
    }

    /**
     * Activate the webhooks of the organization: sets the endpoint and generates the secret (read it with `list()`)
     *
     * @param string $endpoint the URL the events are delivered to — `https://` is added when the scheme is missing
     *
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     * @throws InvalidArgumentException an argument is invalid — nothing was sent
     */
    public function activate(string $endpoint): void
    {
        $this->http->post('/webhook/security/' . $this->organization() . '/activate', ['endpoint' => $endpoint]);
    }

    /**
     * Change the delivery endpoint
     *
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     * @throws InvalidArgumentException an argument is invalid — nothing was sent
     */
    public function updateEndpoint(string $endpoint): void
    {
        $this->http->patch('/webhook/security/' . $this->organization(), ['endpoint' => $endpoint]);
    }

    /**
     * Generate a new secret — it is not returned: read it with `list()`; the previous one stops validating immediately
     *
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     * @throws InvalidArgumentException the scope is not a valid path segment — nothing was sent
     */
    public function regenerate(): void
    {
        $this->http->patch('/webhook/security/' . $this->organization() . '/regenerate');
    }

    /**
     * The secret, the endpoint and the subscriptions of the organization
     *
     * @param bool|null $includeArchived also the `ARCHIVED` subscriptions
     *
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     * @throws InvalidArgumentException an argument is invalid — nothing was sent
     */
    public function list(?bool $includeArchived = null): WebhookSubscriptions
    {
        return WebhookSubscriptions::fromArray(Cast::answer($this->http->get('/webhooks/' . $this->organization(), ['includeArchived' => $includeArchived])));
    }

    /**
     * Subscribe the organization to an event of the catalogue, by name (a `WebhookEventName` constant, or any name) or by uuid
     *
     * @return Created the uuid of the subscription
     *
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     * @throws InvalidArgumentException an argument is invalid — nothing was sent
     */
    public function subscribe(string|WebhookType $event): Created
    {
        $body = ['organization' => $this->http->scope, 'event' => $event instanceof WebhookType ? $event->uuid : $event];

        return Created::fromArray(Cast::answer($this->http->post('/webhooks', $body)));
    }

    /**
     * Archive a subscription: the event is no longer delivered
     *
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     * @throws InvalidArgumentException an argument is invalid — nothing was sent
     */
    public function archive(string|Subscriber $subscriber): void
    {
        $this->http->delete('/webhooks/' . self::subscriber($subscriber));
    }

    /**
     * Reactivate an archived subscription
     *
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     * @throws InvalidArgumentException an argument is invalid — nothing was sent
     */
    public function reactivate(string|Subscriber $subscriber): void
    {
        $this->http->post('/webhooks/' . self::subscriber($subscriber));
    }

    /**
     * The delivery attempts of a subscription
     *
     * @return Page<DeliveryLog>
     *
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     * @throws InvalidArgumentException an argument is invalid — nothing was sent
     */
    public function logs(string|Subscriber $subscriber, ?int $offset = null, ?int $limit = null): Page
    {
        return Page::fromArray(
            Cast::answer($this->http->get('/webhooks/' . self::subscriber($subscriber) . '/logs', ['offset' => $offset, 'limit' => $limit])),
            DeliveryLog::fromArray(...),
        );
    }

    /**
     * The catalogue of events, all states (`ARCHIVED` included)
     *
     * @return Page<WebhookType>
     *
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     */
    public function catalog(): Page
    {
        return Page::fromArray(Cast::answer($this->http->get('/webhook')), WebhookType::fromArray(...));
    }

    /**
     * One event of the catalogue, by uuid or by model
     *
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     * @throws InvalidArgumentException an argument is invalid — nothing was sent
     */
    public function catalogItem(string|WebhookType $webhook): WebhookType
    {
        return WebhookType::fromArray(Cast::answer($this->http->get('/webhook/' . Path::segment($webhook instanceof WebhookType ? $webhook->uuid : $webhook, 'webhook'))));
    }

    /**
     * Verify a delivery received by the endpoint and return its envelope — no request: the check runs locally.
     * Recomputes `HMAC_SHA256(secret, "<timestamp>.<rawBody>")` on the raw bytes, compares it with `X-BITGEN-Signature`
     * in constant time, then checks `X-BITGEN-Timestamp` against `$tolerance`, then parses the JSON envelope.
     *
     * @param string               $rawBody   the body exactly as received (`file_get_contents('php://input')`) — never a re-serialized JSON
     * @param array<string, mixed> $headers   the headers as received: `getallheaders()`, `$_SERVER`, or a PSR-7 `getHeaders()` —
     *                                        names are matched case-insensitively (`X-BITGEN-Signature`, `HTTP_X_BITGEN_SIGNATURE`),
     *                                        a multi-valued header keeps its first value
     * @param string               $secret    the secret of the organization (`list()->secret`)
     * @param int|float            $tolerance maximum distance, in seconds, between now and the timestamp of the delivery — `300` by default, `0` disables the check
     *
     * @throws BitgenException          `$status` 0 and `$errorCode` `missing_signature`, `invalid_signature`, `missing_timestamp`,
     *                                  `timestamp_expired` or `invalid_payload` — the secret never appears in it
     * @throws InvalidArgumentException `$rawBody` or `$secret` empty, `$tolerance` negative or not finite
     */
    public function verify(string $rawBody, array $headers, string $secret, int|float $tolerance = self::DEFAULT_TOLERANCE): WebhookEvent
    {
        if ($rawBody === '') {
            throw new InvalidArgumentException('rawBody must be the received body, a non-empty string');
        }
        if ($secret === '') {
            throw new InvalidArgumentException('secret must be a non-empty string');
        }
        if (!is_finite($tolerance) || $tolerance < 0) {
            throw new InvalidArgumentException('tolerance must be a number of seconds >= 0');
        }

        $signature = self::header($headers, 'x-bitgen-signature');
        if ($signature === null || $signature === '') {
            throw self::rejected('missing_signature');
        }
        $timestamp = self::header($headers, 'x-bitgen-timestamp');
        if ($timestamp === null || $timestamp === '') {
            throw self::rejected('missing_timestamp');
        }

        // Signature first: nothing else is trusted before it matches
        $expected = 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $rawBody, $secret);
        $given = strtolower(trim($signature));
        if (strlen($given) !== strlen($expected) || !hash_equals($expected, $given)) {
            throw self::rejected('invalid_signature');
        }

        if (!is_numeric($timestamp)) {
            throw self::rejected('missing_timestamp');
        }
        if ($tolerance > 0 && abs(time() - (float) $timestamp) > $tolerance) {
            throw self::rejected('timestamp_expired');
        }

        return self::envelope($rawBody);
    }

    /** The scope of the key, as the `{organization}` path segment */
    private function organization(): string
    {
        return Path::segment($this->http->scope, 'scope');
    }

    private static function subscriber(string|Subscriber $subscriber): string
    {
        return Path::segment($subscriber instanceof Subscriber ? $subscriber->uuid : $subscriber, 'subscriber');
    }

    /**
     * Case-insensitive header lookup, `$name` in lowercase `x-bitgen-…` form: matches `X-BITGEN-…` (`getallheaders()`,
     * PSR-7) and `HTTP_X_BITGEN_…` (`$_SERVER`); a multi-valued header keeps its first value
     *
     * @param array<string, mixed> $headers
     */
    private static function header(array $headers, string $name): ?string
    {
        foreach ($headers as $key => $value) {
            $normalized = strtolower((string) $key);
            if (str_starts_with($normalized, 'http_')) {
                $normalized = substr($normalized, 5);
            }
            if (str_replace('_', '-', $normalized) !== $name) {
                continue;
            }
            if (is_array($value)) {
                $value = $value === [] ? null : reset($value);
            }

            return is_scalar($value) ? (string) $value : null;
        }

        return null;
    }

    /**
     * The JSON envelope `{ delivery_id, timestamp, event, data }` — anything else is `invalid_payload`
     *
     * @throws BitgenException
     */
    private static function envelope(string $rawBody): WebhookEvent
    {
        try {
            $decoded = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw self::rejected('invalid_payload');
        }
        if (
            !is_array($decoded)
            || !is_string($decoded['delivery_id'] ?? null)
            || !is_int($decoded['timestamp'] ?? null) && !is_float($decoded['timestamp'] ?? null)
            || !is_string($decoded['event'] ?? null)
            || !array_key_exists('data', $decoded)
        ) {
            throw self::rejected('invalid_payload');
        }

        return WebhookEvent::fromArray(Cast::asObject($decoded));
    }

    /** A verification failure — no HTTP status, and never the secret in the message */
    private static function rejected(string $code): BitgenException
    {
        return new BitgenException(0, $code);
    }
}
