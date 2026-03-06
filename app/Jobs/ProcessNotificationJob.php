<?php

namespace App\Jobs;

use App\Delivery\ProviderFactory;
use App\DTOs\DeliveryRequest;
use App\Enums\DeliveryAttemptStatus;
use App\Events\NotificationFailed;
use App\Events\NotificationSent;
use App\Exceptions\ProviderDeliveryException;
use App\Models\DeliveryAttempt;
use App\Models\Notification;
use App\Services\RateLimiterService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessNotificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries;
    public int $timeout = 30;

    public function __construct(
        public string $notificationId,
    ) {
        $this->tries = (int) config('services.notification.max_attempts', 3);
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(ProviderFactory $providerFactory, RateLimiterService $rateLimiter): void
    {
        $notification = Notification::lockForUpdate()->find($this->notificationId);

        if (! $notification) {
            return;
        }

        if ($notification->status->isTerminal()) {
            return;
        }

        $notification->markAsProcessing();

        if (! $rateLimiter->attempt($notification->channel)) {
            $this->release(10);
            return;
        }

        $startTime = microtime(true);

        $request = new DeliveryRequest(
            notificationId: $notification->id,
            channel:        $notification->channel,
            recipient:      $notification->recipient,
            content:        $notification->content,
            metadata:       $notification->metadata ?? [],
            priority:       $notification->priority,
        );

        $provider = $providerFactory->make($notification->channel);

        try {
            $response   = $provider->send($request);
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            $notification->markAsSent($response);

            event(new NotificationSent($notification, $response, $durationMs));

        } catch (ProviderDeliveryException $e) {
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            DeliveryAttempt::create([
                'notification_id'      => $notification->id,
                'attempt_number'       => $this->attempts(),
                'status'               => $e->isPermanent
                    ? DeliveryAttemptStatus::FAILED
                    : DeliveryAttemptStatus::RATE_LIMITED,
                'provider'             => $provider->name(),
                'error_message'        => $e->getMessage(),
                'is_transient_failure' => ! $e->isPermanent,
                'duration_ms'          => $durationMs,
                'attempted_at'         => now(),
            ]);

            if ($e->isPermanent) {
                $notification->markAsFailed($e->getMessage());
                event(new NotificationFailed($notification, $e->getMessage(), true, $durationMs));
                return;
            }

            event(new NotificationFailed($notification, $e->getMessage(), false, $durationMs));
            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        $notification = Notification::find($this->notificationId);

        if ($notification) {
            $notification->markAsFailed($e->getMessage());
        }
    }
}
