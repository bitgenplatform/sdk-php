<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Tests\Http;

use Bitgen\Sdk\Http\Response;
use Bitgen\Sdk\Http\Transport;
use Bitgen\Sdk\Http\TransportException;

/**
 * Records every request and answers with the queued responses (a TransportException in the queue is thrown).
 */
final class FakeTransport implements Transport
{
    /** @var list<array{method: string, url: string, headers: array<string, string>, body: ?string, timeoutMs: int}> */
    public array $requests = [];

    /** @var list<Response|TransportException> */
    private array $queue = [];

    public function willAnswer(int $status, string $body = '', string $reason = ''): self
    {
        $this->queue[] = new Response($status, $reason, $body);

        return $this;
    }

    public function willFail(TransportException $exception): self
    {
        $this->queue[] = $exception;

        return $this;
    }

    public function send(string $method, string $url, array $headers, ?string $body, int $timeoutMs): Response
    {
        $this->requests[] = ['method' => $method, 'url' => $url, 'headers' => $headers, 'body' => $body, 'timeoutMs' => $timeoutMs];
        $next = array_shift($this->queue) ?? new Response(200, 'OK', '{}');
        if ($next instanceof TransportException) {
            throw $next;
        }

        return $next;
    }

    /** @return array{method: string, url: string, headers: array<string, string>, body: ?string, timeoutMs: int} */
    public function last(): array
    {
        $last = end($this->requests);
        if ($last === false) {
            throw new \LogicException('no request was sent');
        }

        return $last;
    }
}
