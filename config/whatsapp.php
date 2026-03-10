<?php

return [
    'account_sid' => env('TWILIO_ACCOUNT_SID'),
    'auth_token' => env('TWILIO_AUTH_TOKEN'),

    'warmup' => [
        'enabled' => true,
        'start_date' => env('WHATSAPP_WARMUP_START_DATE'),
        'daily_limits' => [
            1 => 50,    // S1
            2 => 100,   // S2
            3 => 200,   // S3
            4 => 350,   // S4
            5 => 500,   // S5
            6 => 750,   // S6
            7 => 1000,  // S7
            8 => 1500,  // S8
        ],
    ],

    'safety' => [
        'max_percent_of_tier' => 70,
        'max_per_chatter_per_day' => 1,
        'max_per_chatter_per_week' => 4,
        'quiet_hours_start' => '22:00',
        'quiet_hours_end' => '09:00',
        'min_gap_hours' => 4,
        'max_emojis' => 3,
        'min_personalized_words' => 10,
    ],

    'budget' => [
        'daily_cap_cents' => (int) env('WHATSAPP_DAILY_BUDGET_CENTS', 2000),
        'monthly_cap_cents' => (int) env('WHATSAPP_MONTHLY_BUDGET_CENTS', 30000),
        'alert_at_percent' => 80,
        'auto_stop_at_cap' => true,
    ],

    'circuit_breaker' => [
        'reduce_threshold' => 0.005,   // >0.5% block rate
        'pause_threshold' => 0.01,     // >1% block rate
        'stop_threshold' => 0.02,      // >2% block rate
        'consecutive_failures_pause' => 10,
        'cool_down_minutes' => 60,
    ],

    'rate_limits' => [
        'per_chatter_daily' => 1,
        'per_chatter_weekly' => 4,
        'global_per_second' => 80,
    ],

    'cost_per_country' => [
        'US' => 0.0042,
        'BR' => 0.0300,
        'IN' => 0.0040,
        'FR' => 0.0720,
        'DE' => 0.0720,
        'GB' => 0.0360,
        'ES' => 0.0300,
        'default' => 0.0500,
    ],
];
