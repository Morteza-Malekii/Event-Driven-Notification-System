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

    public function test_blocks_request_after_limit_exceeded(): void
    {
        // Set limit to 1 so we only need 2 requests to verify blocking.
        // The rate limiter uses timestamps (ms) as Redis sorted-set members,
        // so we sleep 2 ms between calls to ensure unique member keys.
        config(['notification.rate_limit.per_second' => 1]);
        $limiter = app(RateLimiterService::class);

        $this->assertTrue($limiter->attempt(NotificationChannel::SMS));
        usleep(2000);
        $this->assertFalse($limiter->attempt(NotificationChannel::SMS));
    }

    public function test_current_count_reflects_attempts(): void
    {
        // Sleep 2 ms between attempts so each gets a unique ms-timestamp member.
        $limiter = app(RateLimiterService::class);

        $limiter->attempt(NotificationChannel::PUSH);
        usleep(2000);
        $limiter->attempt(NotificationChannel::PUSH);
        usleep(2000);
        $limiter->attempt(NotificationChannel::PUSH);

        $key = 'rate_limit:channel:'.NotificationChannel::PUSH->value;
        $now = (int) (microtime(true) * 1000);
        Redis::zremrangebyscore($key, '-inf', $now - 1000);
        $this->assertEquals(3, (int) Redis::zcard($key));
    }
}
