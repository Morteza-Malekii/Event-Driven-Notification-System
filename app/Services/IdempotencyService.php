<?php

namespace App\Services;

use App\Exceptions\DuplicateIdempotencyKeyException;
use App\Models\IdempotencyKey;
use Illuminate\Support\Facades\Redis;

class IdempotencyService
{
    private const TTL = 86400;
    private const REDIS_PREFIX = 'idempotency:';

    public function hashRequest(array $data): string
    {
        return hash('sha256', json_encode($data));
    }

    public function check(string $key, string $hash): ?array
    {
        $redisKey = self::REDIS_PREFIX . $key;
        $cached   = Redis::get($redisKey);

        if ($cached !== null) {
            $data = json_decode($cached, true);

            if ($data['hash'] !== $hash) {
                throw new DuplicateIdempotencyKeyException($key);
            }

            return $data['response'];
        }

        $record = IdempotencyKey::where('key', $key)->first();

        if ($record === null) {
            return null;
        }

        if ($record->request_hash !== $hash) {
            throw new DuplicateIdempotencyKeyException($key);
        }

        return $record->response_cache;
    }

    public function store(string $key, string $hash, string $notificationId, array $response): void
    {
        $redisKey = self::REDIS_PREFIX . $key;
        $payload  = json_encode(['hash' => $hash, 'response' => $response]);

        Redis::setex($redisKey, self::TTL, $payload);

        IdempotencyKey::create([
            'key'             => $key,
            'request_hash'    => $hash,
            'notification_id' => $notificationId,
            'response_cache'  => $response,
            'expires_at'      => now()->addSeconds(self::TTL),
        ]);
    }

    public function storeBatch(string $key, string $hash, string $batchId, array $response): void
    {
        $redisKey = self::REDIS_PREFIX . $key;
        $payload  = json_encode(['hash' => $hash, 'response' => $response]);

        Redis::setex($redisKey, self::TTL, $payload);

        IdempotencyKey::create([
            'key'          => $key,
            'request_hash' => $hash,
            'batch_id'     => $batchId,
            'response_cache' => $response,
            'expires_at'   => now()->addSeconds(self::TTL),
        ]);
    }
}
