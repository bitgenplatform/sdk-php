<?php

declare(strict_types=1);

namespace Bitgen\Sdk;

use InvalidArgumentException;

readonly class Config
{
    public const string ENV_SANDBOX    = 'sandbox';
    public const string ENV_PRODUCTION = 'production';
    public const string ENV_LOCALHOST  = 'localhost';

    private const array ENV_BASE_URLS = [
        self::ENV_SANDBOX    => 'https://api.staging.btgn.dev',
        self::ENV_PRODUCTION => 'https://api.bitgen.com',
        self::ENV_LOCALHOST  => 'http://localhost:14303',
    ];

    public readonly string $baseUrl;

    public function __construct(
        public readonly string $scope,
        public readonly string $apiKey,
        public readonly string $env  = self::ENV_SANDBOX,
        ?string                $host = null,
        ?int                   $port  = null,
        public readonly bool   $isSsl = true,
    ) {
        if ($host !== null) {
            $port          = $port ?? 80;
            $scheme        = $this->isSsl ? 'https' : 'http';
            $this->baseUrl = sprintf('%s://%s:%d', $scheme, $host, $port);
            return;
        }

        if (!isset(self::ENV_BASE_URLS[$this->env])) {
            throw new InvalidArgumentException(
                sprintf('Invalid env "%s". Expected "sandbox", "production" or "localhost".', $this->env)
            );
        }

        $this->baseUrl = self::ENV_BASE_URLS[$this->env];
    }
}