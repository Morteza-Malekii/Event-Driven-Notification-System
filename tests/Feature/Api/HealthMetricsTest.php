<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class HealthMetricsTest extends TestCase
{
    public function test_health_returns_healthy(): void
    {
        $this->getJson('/api/health')
             ->assertStatus(200)
             ->assertJsonPath('data.status', 'healthy')
             ->assertJsonStructure(['data' => ['services' => ['database', 'redis', 'cache']]]);
    }

    public function test_metrics_structure(): void
    {
        $this->getJson('/api/metrics')
             ->assertStatus(200)
             ->assertJsonStructure(['data' => ['notifications', 'channels', 'queues', 'generated_at']]);
    }
}
