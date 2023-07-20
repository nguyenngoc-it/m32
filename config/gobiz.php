<?php

return [
    'm10' => [
        'url' => env('M10_URL', 'https://app.authen.me'),
        'client_id' => env('M10_CLIENT_ID'),
        'client_secret' => env('M10_CLIENT_SECRET'),
    ],

    'webhook' => [
        'url' => env('WEBHOOK_URL', 'https://webhook.vinasat.gobizdev.com/api'),
        'token' => env('WEBHOOK_TOKEN'),
    ],

    'utils' => [
        'url' => env('UTILS_URL', 'https://utils.shipping.mygobiz.net'),
        'key' => env('UTILS_KEY'),
        'ttl' => env('UTILS_TTL', 3600),
    ],
];
