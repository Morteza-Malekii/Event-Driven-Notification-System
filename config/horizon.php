<?php

return [

    'defaults' => [
        'supervisor-high' => [
            'connection' => 'redis',
            'queue'      => ['notifications-high'],
            'balance'    => 'simple',
            'processes'  => 5,
            'tries'      => 3,
            'nice'       => 0,
        ],

        'supervisor-normal' => [
            'connection' => 'redis',
            'queue'      => ['notifications-normal'],
            'balance'    => 'simple',
            'processes'  => 3,
            'tries'      => 3,
            'nice'       => 5,
        ],

        'supervisor-low' => [
            'connection' => 'redis',
            'queue'      => ['notifications-low'],
            'balance'    => 'simple',
            'processes'  => 2,
            'tries'      => 3,
            'nice'       => 10,
        ],
    ],

    'environments' => [
        'production' => [
            'supervisor-high'   => ['processes' => 5],
            'supervisor-normal' => ['processes' => 3],
            'supervisor-low'    => ['processes' => 2],
        ],

        'local' => [
            'supervisor-high'   => ['processes' => 1],
            'supervisor-normal' => ['processes' => 1],
            'supervisor-low'    => ['processes' => 1],
        ],
    ],

];
