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

    public function handleSent(NotificationSent $event): void
    {
        $this->metrics->incrementSent($event->notification->channel);
        $this->metrics->recordLatency($event->notification->channel, $event->durationMs);
    }

    public function handleFailed(NotificationFailed $event): void
    {
        $this->metrics->incrementFailed($event->notification->channel);
        $this->metrics->recordLatency($event->notification->channel, $event->durationMs);
    }
}
