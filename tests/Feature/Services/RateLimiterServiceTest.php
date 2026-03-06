<?php

namespace Tests\Feature\Services;

use App\Enums\NotificationChannel;
use App\Services\RateLimiterService;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class RateLimiterServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach (NotificationChannel::cases() as $channel) {
            Redis::del("rate_limit:channel:{$channel->value}");
        }
    }

    public function test_allows_requests_under_limit(): void
    {
        $limiter = app(RateLimiterService::class);
        for ($i = 0; $i < 10; $i++) {
            $this->assertTrue($limiter->attempt(NotificationChannel::SMS));
        }
    }

    public function test_channels_are_independent(): void
    {
        $limiter = app(RateLimiterService::class);
        for ($i = 0; $i < 100; $i++) {
            $limiter->attempt(NotificationChannel::SMS);
        }
        $this->assertTrue($limiter->attempt(NotificationChannel::EMAIL));
    }

    public function test_limit_is_100(): void
    {
        $this->assertEquals(100, app(RateLimiterService::class)->getLimit());
    }
}
