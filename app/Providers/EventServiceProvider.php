<?php

namespace App\Providers;

use App\Events\NotificationCanceled;
use App\Events\NotificationCreated;
use App\Events\NotificationFailed;
use App\Events\NotificationSent;
use App\Listeners\DispatchNotificationJobListener;
use App\Listeners\RecordMetricsListener;
use App\Listeners\SyncBatchCountersListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        NotificationCreated::class => [
            DispatchNotificationJobListener::class,
        ],
        NotificationSent::class => [
            RecordMetricsListener::class,
            SyncBatchCountersListener::class,
        ],
        NotificationFailed::class => [
            RecordMetricsListener::class,
            SyncBatchCountersListener::class,
        ],
        NotificationCanceled::class => [
            SyncBatchCountersListener::class,
        ],
    ];
}
