<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Resources;

use Bitgen\Sdk\HttpClient;
use Bitgen\Sdk\Models\HealthCheck;
use Bitgen\Sdk\Models\Stats;

class StatsResource
{
    public function __construct(private readonly HttpClient $http) {}

    public function health(): HealthCheck
    {
        $data = $this->http->get('/health');

        return HealthCheck::fromArray($data);
    }

    public function quotas(): Stats
    {
        $data = $this->http->get('/api/v3/stats');

        return Stats::fromArray($data);
    }
}
