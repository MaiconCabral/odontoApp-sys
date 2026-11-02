<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        env('FRONTEND_URL', 'http://127.0.0.1:3000'),
        'http://127.0.0.1:3000',
        'http://localhost:3000',
    ],

    'allowed_origins_patterns' => array_filter([env('CORS_ALLOWED_ORIGINS_PATTERN', '#^http://([a-z0-9-]+\.)?localhost(:\d+)?$#')]),

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
