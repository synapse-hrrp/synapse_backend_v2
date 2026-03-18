<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:3000',
        'http://127.0.0.1:3000',
        // ajoute l’IP LAN si nécessaire
        'http://192.168.1.72:3000',
    ],

    'allowed_origins_patterns' => [
        // '/^http:\/\/192\.168\.1\.\d+:3000$/',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'Retry-After',
    ],

    'max_age' => 86400,

    // Bearer tokens => false
    'supports_credentials' => false,
];
 