<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'firebase' => [
        'webhook_secret' => env('FIREBASE_WEBHOOK_SECRET'),
        'reconcile_url' => env('FIREBASE_RECONCILE_URL'),
        'timeout' => 10,
        'retry_times' => 3,
        'retry_sleep' => 500,
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'admin_chat_id' => env('TELEGRAM_ADMIN_CHAT_ID'),
        'timeout' => 10,
        'retry_times' => 3,
        'retry_sleep' => 1000,
    ],

    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'whatsapp_from' => env('TWILIO_WHATSAPP_FROM'),
        'timeout' => 15,
        'retry_times' => 2,
        'retry_sleep' => 2000,
    ],

    'admin_ip_whitelist' => env('ADMIN_ALLOWED_IPS') ? explode(',', env('ADMIN_ALLOWED_IPS')) : [],

];
