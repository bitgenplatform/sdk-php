<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Tests\Resource;

use Bitgen\Sdk\Exception\BitgenException;
use Bitgen\Sdk\Http\HttpClient;
use Bitgen\Sdk\Model\WebhookEventName;
use Bitgen\Sdk\Resource\WebhooksResource;
use Bitgen\Sdk\Tests\Http\FakeTransport;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/** `WebhooksResource::verify()` — local verification of a received delivery, never a request */
final class WebhooksVerifyTest extends TestCase
{
    private const SECRET = 'whsec_0123456789abcdef';

    /** The bytes as delivered — spaces and key order matter for the signature */
    private const BODY = '{"delivery_id":"d-1","timestamp":1700000000,"event":"custody.sent","data":{"wallet":"w-1","amount":"0.5"}}';

    private FakeTransport $transport;
    private WebhooksResource $webhooks;

    protected function setUp(): void
    {
        $this->transport = new FakeTransport();
        $this->webhooks = new WebhooksResource(new HttpClient($this->transport, 'org-uuid', 'k', 'https://api.test', 1000, 'ua'));
    }

    private static function sign(string $body, string $timestamp, string $secret = self::SECRET): string
    {
        return 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $body, $secret);
    }

    /** @return array<string, string> */
    private static function headers(string $body, ?int $issuedAt = null, string $secret = self::SECRET): array
    {
        $timestamp = (string) ($issuedAt ?? time());

        return ['X-BITGEN-Timestamp' => $timestamp, 'X-BITGEN-Signature' => self::sign($body, $timestamp, $secret)];
    }

    /** @param array<string, mixed> $headers */
    private function rejected(string $body, array $headers, string $secret = self::SECRET, int|float $tolerance = 300): string
    {
        try {
            $this->webhooks->verify($body, $headers, $secret, $tolerance);
        } catch (BitgenException $e) {
            self::assertSame(0, $e->status);
            self::assertSame($e->errorCode . ' (HTTP 0)', $e->getMessage());
            self::assertStringNotContainsString(self::SECRET, $e->getMessage());
            self::assertNull($e->getPrevious());

            return $e->errorCode;
        }
        self::fail('expected a BitgenException');
    }

    public function testAValidDeliveryIsReturnedAsAnEnvelope(): void
    {
        $event = $this->webhooks->verify(self::BODY, self::headers(self::BODY), self::SECRET);

        self::assertSame('d-1', $event->delivery_id);
        self::assertSame(1700000000, $event->timestamp);
        self::assertSame(WebhookEventName::CUSTODY_SENT, $event->event);
        self::assertSame(['wallet' => 'w-1', 'amount' => '0.5'], $event->data);
        self::assertSame([], $this->transport->requests);   // never a request
    }

    public function testHeaderNamesAreMatchedCaseInsensitivelyInEveryShape(): void
    {
        $timestamp = (string) time();
        $signature = self::sign(self::BODY, $timestamp);
        $shapes = [
            'getallheaders()' => ['Content-Type' => 'application/json', 'x-bitgen-timestamp' => $timestamp, 'X-Bitgen-Signature' => $signature],
            '$_SERVER' => ['REQUEST_METHOD' => 'POST', 'HTTP_X_BITGEN_TIMESTAMP' => $timestamp, 'HTTP_X_BITGEN_SIGNATURE' => $signature, 'argv' => ['x']],
            'PSR-7 getHeaders()' => ['X-BITGEN-Timestamp' => [$timestamp], 'X-BITGEN-Signature' => [$signature, 'sha256=second-value-ignored']],
            'signature in upper case with spaces' => ['X-BITGEN-Timestamp' => $timestamp, 'X-BITGEN-Signature' => '  ' . strtoupper($signature) . ' '],
        ];
        foreach ($shapes as $shape => $headers) {
            self::assertSame('d-1', $this->webhooks->verify(self::BODY, $headers, self::SECRET)->delivery_id, $shape);
        }
    }

    public function testAReserializedBodyDoesNotVerify(): void
    {
        $headers = self::headers(self::BODY);
        $reserialized = json_encode(json_decode(self::BODY, true), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
        self::assertNotSame(self::BODY, $reserialized);
        self::assertSame('invalid_signature', $this->rejected($reserialized, $headers));
    }

    public function testATamperedBodyAWrongSecretOrAForgedSignatureIsInvalid(): void
    {
        $headers = self::headers(self::BODY);
        self::assertSame('invalid_signature', $this->rejected(str_replace('"0.5"', '"5.0"', self::BODY), $headers));
        self::assertSame('invalid_signature', $this->rejected(self::BODY, $headers, 'another-secret'));
        // a forged signature of the right length
        $forged = $headers;
        $forged['X-BITGEN-Signature'] = 'sha256=' . str_repeat('0', 64);
        self::assertSame('invalid_signature', $this->rejected(self::BODY, $forged));
        // the wrong length (a truncated or non-hex value) is invalid too, never an error
        $short = $headers;
        $short['X-BITGEN-Signature'] = 'sha256=abc';
        self::assertSame('invalid_signature', $this->rejected(self::BODY, $short));
        self::assertSame([], $this->transport->requests);
    }

    public function testMissingHeaders(): void
    {
        $headers = self::headers(self::BODY);
        self::assertSame('missing_signature', $this->rejected(self::BODY, []));
        self::assertSame('missing_signature', $this->rejected(self::BODY, ['X-BITGEN-Timestamp' => $headers['X-BITGEN-Timestamp']]));
        self::assertSame('missing_signature', $this->rejected(self::BODY, ['X-BITGEN-Signature' => '', 'X-BITGEN-Timestamp' => $headers['X-BITGEN-Timestamp']]));
        self::assertSame('missing_signature', $this->rejected(self::BODY, ['X-BITGEN-Signature' => null, 'X-BITGEN-Timestamp' => $headers['X-BITGEN-Timestamp']]));
        self::assertSame('missing_timestamp', $this->rejected(self::BODY, ['X-BITGEN-Signature' => $headers['X-BITGEN-Signature']]));
        self::assertSame('missing_timestamp', $this->rejected(self::BODY, ['X-BITGEN-Signature' => $headers['X-BITGEN-Signature'], 'X-BITGEN-Timestamp' => '']));
        self::assertSame('missing_timestamp', $this->rejected(self::BODY, ['X-BITGEN-Signature' => $headers['X-BITGEN-Signature'], 'X-BITGEN-Timestamp' => ['']]));
        // a timestamp that is not a number: the signature is checked with it first, then it is refused
        $signed = self::sign(self::BODY, 'yesterday');
        self::assertSame('missing_timestamp', $this->rejected(self::BODY, ['X-BITGEN-Signature' => $signed, 'X-BITGEN-Timestamp' => 'yesterday']));
    }

    public function testFreshness(): void
    {
        self::assertSame('timestamp_expired', $this->rejected(self::BODY, self::headers(self::BODY, time() - 301)));
        self::assertSame('d-1', $this->webhooks->verify(self::BODY, self::headers(self::BODY, time() - 299), self::SECRET)->delivery_id);
        self::assertSame('timestamp_expired', $this->rejected(self::BODY, self::headers(self::BODY, time() + 301)));
        self::assertSame('d-1', $this->webhooks->verify(self::BODY, self::headers(self::BODY, time() + 299), self::SECRET)->delivery_id);
        // a custom window, and 0 disables the check
        self::assertSame('timestamp_expired', $this->rejected(self::BODY, self::headers(self::BODY, time() - 61), self::SECRET, 60));
        self::assertSame('d-1', $this->webhooks->verify(self::BODY, self::headers(self::BODY, time() - 59), self::SECRET, 60.5)->delivery_id);
        self::assertSame('d-1', $this->webhooks->verify(self::BODY, self::headers(self::BODY, 1700000000), self::SECRET, 0)->delivery_id);
    }

    public function testTheSignatureIsCheckedBeforeTheFreshness(): void
    {
        $headers = self::headers(self::BODY, time() - 3600);
        $headers['X-BITGEN-Signature'] = self::sign(self::BODY, $headers['X-BITGEN-Timestamp'], 'another-secret');
        self::assertSame('invalid_signature', $this->rejected(self::BODY, $headers));
    }

    public function testAValidSignatureOnANonEnvelopeIsAnInvalidPayload(): void
    {
        $bodies = [
            'not json' => 'delivery_id=d-1',
            'a list' => '[1,2]',
            'a string' => '"text"',
            'no delivery_id' => '{"timestamp":1700000000,"event":"custody.sent","data":{}}',
            'timestamp as a string' => '{"delivery_id":"d-1","timestamp":"1700000000","event":"custody.sent","data":{}}',
            'no event' => '{"delivery_id":"d-1","timestamp":1700000000,"data":{}}',
            'no data' => '{"delivery_id":"d-1","timestamp":1700000000,"event":"custody.sent"}',
        ];
        foreach ($bodies as $case => $body) {
            self::assertSame('invalid_payload', $this->rejected($body, self::headers($body)), $case);
        }
        // `data` may be null or a scalar: its shape depends on the event
        $event = $this->webhooks->verify('{"delivery_id":"d-2","timestamp":1700000000.0,"event":"alert.status","data":null}', self::headers('{"delivery_id":"d-2","timestamp":1700000000.0,"event":"alert.status","data":null}'), self::SECRET);
        self::assertNull($event->data);
        self::assertSame(1700000000, $event->timestamp);
    }

    public function testUnusableArgumentsAreInvalidArguments(): void
    {
        $headers = self::headers(self::BODY);
        $cases = [
            'rawBody must be the received body, a non-empty string' => fn () => $this->webhooks->verify('', $headers, self::SECRET),
            'secret must be a non-empty string' => fn () => $this->webhooks->verify(self::BODY, $headers, ''),
            'tolerance must be a number of seconds >= 0' => fn () => $this->webhooks->verify(self::BODY, $headers, self::SECRET, -1),
        ];
        foreach ($cases as $message => $call) {
            try {
                $call();
                self::fail('expected an InvalidArgumentException');
            } catch (InvalidArgumentException $e) {
                self::assertSame($message, $e->getMessage());
            }
        }
        foreach ([NAN, INF] as $tolerance) {
            try {
                $this->webhooks->verify(self::BODY, $headers, self::SECRET, $tolerance);
                self::fail('expected an InvalidArgumentException');
            } catch (InvalidArgumentException) {
            }
        }
        self::assertSame([], $this->transport->requests);
    }
}
