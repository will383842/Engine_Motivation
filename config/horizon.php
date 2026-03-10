<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Horizon Domain
    |--------------------------------------------------------------------------
    */

    'domain' => env('HORIZON_DOMAIN'),

    'path' => 'horizon',

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Connection
    |--------------------------------------------------------------------------
    */

    'use' => 'default',

    'prefix' => env('HORIZON_PREFIX', Str::slug(env('APP_NAME', 'motivation'), '_') . '_horizon:'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Route Middleware
    |--------------------------------------------------------------------------
    */

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Queue Worker Configuration
    |--------------------------------------------------------------------------
    |
    | 6 queues ordered by priority:
    | critical > high > webhooks > default > whatsapp > low
    |
    */

    'defaults' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['critical', 'high', 'webhooks', 'default', 'whatsapp', 'low'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 10,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 128,
            'tries' => 3,
            'timeout' => 60,
            'nice' => 0,
        ],
    ],

    'environments' => [
        'production' => [
            'supervisor-critical' => [
                'connection' => 'redis',
                'queue' => ['critical'],
                'balance' => 'false',
                'processes' => 2,
                'tries' => 1,
                'timeout' => 30,
            ],
            'supervisor-high' => [
                'connection' => 'redis',
                'queue' => ['high', 'webhooks'],
                'balance' => 'auto',
                'minProcesses' => 2,
                'maxProcesses' => 6,
                'tries' => 3,
                'timeout' => 60,
            ],
            'supervisor-default' => [
                'connection' => 'redis',
                'queue' => ['default'],
                'balance' => 'auto',
                'minProcesses' => 2,
                'maxProcesses' => 8,
                'tries' => 3,
                'timeout' => 120,
            ],
            'supervisor-whatsapp' => [
                'connection' => 'redis',
                'queue' => ['whatsapp'],
                'balance' => 'auto',
                'minProcesses' => 1,
                'maxProcesses' => 4,
                'tries' => 3,
                'timeout' => 30,
            ],
            'supervisor-low' => [
                'connection' => 'redis',
                'queue' => ['low'],
                'balance' => 'auto',
                'minProcesses' => 1,
                'maxProcesses' => 3,
                'tries' => 5,
                'timeout' => 300,
            ],
        ],

        'local' => [
            'supervisor-1' => [
                'connection' => 'redis',
                'queue' => ['critical', 'high', 'webhooks', 'default', 'whatsapp', 'low'],
                'balance' => 'auto',
                'processes' => 3,
                'tries' => 3,
                'timeout' => 120,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Metrics
    |--------------------------------------------------------------------------
    */

    'metrics' => [
        'trim_snapshots' => [
            'job' => 24,
            'queue' => 24,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fast Termination
    |--------------------------------------------------------------------------
    */

    'fast_termination' => false,

    /*
    |--------------------------------------------------------------------------
    | Memory Limit (MB)
    |--------------------------------------------------------------------------
    */

    'memory_limit' => 64,

];
