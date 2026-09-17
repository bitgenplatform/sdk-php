<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Http;

use Bitgen\Sdk\Env;
use Bitgen\Sdk\Support\Enum;
use InvalidArgumentException;

/**
 * The base URL of the API for a configuration: `scheme://host[:port]`, no trailing slash, no version prefix.
 *
 * @internal
 */
final class BaseUrl
{
    public const LOCALHOST_PORT = 3002;

    /**
     * @param string $env one of `Env::VALUES`
     *
     * @throws InvalidArgumentException `env` is unknown, `host` is not a bare hostname, or `port` is out of range
     */
    public static function resolve(string $env, ?string $host, ?int $port, bool $isSsl): string
    {
        Enum::ensure($env, Env::VALUES, 'env');
        if ($port !== null && ($port < 1 || $port > 65535)) {
            throw new InvalidArgumentException('port must be an integer between 1 and 65535');
        }
        // Custom host (a container, a tunnel): bare hostname, scheme and port come from isSsl / port
        if ($host !== null) {
            // Letters, digits, dots, hyphens (and underscores of internal DNS names): a scheme, a port, a path, userinfo or a bracketed IPv6 are refused
            if (preg_match('/^[A-Za-z0-9._-]+$/', $host) !== 1) {
                throw new InvalidArgumentException('host must be a bare hostname (no scheme, port or path): use port and isSsl');
            }

            return sprintf('%s://%s:%d', $isSsl ? 'https' : 'http', $host, $port ?? 80);
        }

        return match ($env) {
            Env::PRODUCTION => 'https://api.bitgen.com',
            Env::SANDBOX => 'https://api.sandbox.bitgen.com',
            Env::STAGING => 'https://api.staging.btgn.dev',
            default => 'http://localhost:' . ($port ?? self::LOCALHOST_PORT),
        };
    }
}
