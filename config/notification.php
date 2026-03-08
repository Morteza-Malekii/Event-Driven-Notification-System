<?php

return [

    'retry' => [
        'max_attempts' => env('NOTIFICATION_MAX_ATTEMPTS', 3),
    ],

    'rate_limit' => [
        'per_second' => env('NOTIFICATION_RATE_LIMIT_PER_SECOND', 100),
    ],

    'providers' => [
        'webhook_site' => [
            'url'     => env('NOTIFICATION_WEBHOOK_URL'),
            'timeout' => env('NOTIFICATION_WEBHOOK_TIMEOUT', 15),
        ],
    ],

];
