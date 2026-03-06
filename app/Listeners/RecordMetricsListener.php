<?php

namespace App\Listeners;

use App\Events\NotificationFailed;
use App\Events\NotificationSent;
use App\Services\MetricsService;

class RecordMetricsListener
{
    public function __construct(
        private readonly MetricsService $metrics,
    ) {}

    public function handle(NotificationSent|NotificationFailed $event): void
    {
        if ($event instanceof NotificationSent) {
            $this->metrics->incrementSent($event->notification->channel);
            $this->metrics->recordLatency($event->notification->channel, $event->durationMs);
        } else {
            $this->metrics->incrementFailed($event->notification->channel);
            $this->metrics->recordLatency($event->notification->channel, $event->durationMs);
        }
    }
}
