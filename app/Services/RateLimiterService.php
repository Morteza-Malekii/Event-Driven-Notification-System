<?php

namespace App\Services;

use App\Enums\NotificationChannel;
use Illuminate\Support\Facades\Redis;

class RateLimiterService
{
    private const WINDOW_SECONDS = 60;

    private string $luaScript = <<<'LUA'
        local key    = KEYS[1]
        local now    = tonumber(ARGV[1])
        local window = tonumber(ARGV[2])
        local limit  = tonumber(ARGV[3])

        redis.call('ZREMRANGEBYSCORE', key, '-inf', now - window * 1000)
        local count = redis.call('ZCARD', key)

        if count < limit then
            redis.call('ZADD', key, now, now)
            redis.call('EXPIRE', key, window)
            return 1
        end

        return 0
    LUA;

    public function attempt(NotificationChannel $channel): bool
    {
        $key    = $this->key($channel);
        $now    = (int) (microtime(true) * 1000);
        $limit  = $this->getLimit();

        $result = Redis::eval(
            $this->luaScript,
            1,
            $key,
            $now,
            self::WINDOW_SECONDS,
            $limit,
        );

        return (bool) $result;
    }

    public function currentCount(NotificationChannel $channel): int
    {
        $key = $this->key($channel);
        $now = (int) (microtime(true) * 1000);

        Redis::zremrangebyscore($key, '-inf', $now - self::WINDOW_SECONDS * 1000);

        return (int) Redis::zcard($key);
    }

    public function getLimit(): int
    {
        return (int) config('notification.rate_limit.per_second', 100);
    }

    private function key(NotificationChannel $channel): string
    {
        return "rate_limit:channel:{$channel->value}";
    }
}
