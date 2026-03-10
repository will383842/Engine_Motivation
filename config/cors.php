<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        env('APP_URL', 'https://motivation.life-expat.com'),
        'https://sos-expat.com',
        'https://www.sos-expat.com',
        'https://life-expat.com',
        'https://www.life-expat.com',
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
