<?php

return [

    'retry' => [
        'max_attempts' => env('NOTIFICATION_MAX_ATTEMPTS', 3),
    ],

    'rate_limit' => env('NOTIFICATION_RATE_LIMIT', 100),

    'webhook' => [
        'url'     => env('NOTIFICATION_WEBHOOK_URL'),
        'timeout' => env('NOTIFICATION_WEBHOOK_TIMEOUT', 15),
    ],

];
