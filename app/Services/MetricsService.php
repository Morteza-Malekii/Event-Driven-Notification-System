<?php

namespace App\Services;

use App\Enums\NotificationChannel;
use Illuminate\Support\Facades\Redis;

class MetricsService
{
    private const LATENCY_SAMPLE_SIZE = 1000;
    private const PREFIX = 'metrics:';

    public function incrementSent(NotificationChannel $channel): void
    {
        Redis::incr(self::PREFIX . "sent:{$channel->value}");
    }

    public function incrementFailed(NotificationChannel $channel): void
    {
        Redis::incr(self::PREFIX . "failed:{$channel->value}");
    }

    public function recordLatency(NotificationChannel $channel, int $milliseconds): void
    {
        $key = self::PREFIX . "latency:{$channel->value}";

        Redis::lpush($key, $milliseconds);
        Redis::ltrim($key, 0, self::LATENCY_SAMPLE_SIZE - 1);
    }

    public function getSnapshot(): array
    {
        $snapshot = [];

        foreach (NotificationChannel::cases() as $channel) {
            $sent   = (int) Redis::get(self::PREFIX . "sent:{$channel->value}");
            $failed = (int) Redis::get(self::PREFIX . "failed:{$channel->value}");
            $samples = Redis::lrange(self::PREFIX . "latency:{$channel->value}", 0, -1);

            $snapshot[$channel->value] = [
                'sent'            => $sent,
                'failed'          => $failed,
                'avg_latency_ms'  => count($samples) > 0
                    ? round(array_sum($samples) / count($samples), 2)
                    : null,
                'sample_count'    => count($samples),
            ];
        }

        return $snapshot;
    }
}
