<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, SparkPost and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'ses' => [
        'key' => env('SES_KEY'),
        'secret' => env('SES_SECRET'),
        'region' => env('SES_REGION', 'us-east-1'),
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'stripe' => [
        'model' => \Modules\User\Models\User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook' => [
            'secret' => env('STRIPE_WEBHOOK_SECRET'),
            'tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
        ],
    ],

    'ghn' => [
        'api_url' => env('GHN_API_URL', 'https://dev-online-gateway.ghn.vn/'),
    ],

    'jntp' => [
        'api_url' => env('JNTP_API_URL', 'https://test-api.jtexpress.ph/'),
    ],

    'shippo' => [
        'api_url' => env('SHIPPO_API_URL', 'https://dathang.orderhang.com/'),
    ],

    'jntvn' => [
        'api_url' => env('JNTVN_API_URL', 'http://47.57.106.86/'),
    ],

    'snappy' => [
        'api_url' => env('SNAPPY_API_URL', 'https://pos.pages.fm/api/v1/'),
    ],

    'lwe' => [
        'api_url' => env('LWE_API_URL', 'https://lweph.com/api/v1/'),
    ],

    'sapi' => [
        'api_url' => env('SAPI_API_URL', 'http://apisanbox.coresyssap.com/'),
        'track_url' => env('SAPI_TRACK_URL', 'http://apisanbox.coresyssap.com'),
    ],

    'nijavai' => [
        'api_url' => env('NIJAVAI_API_URL', 'https://api-sandbox.ninjavan.co/SG/'),
    ],

    'nijavam' => [
        'api_url' => env('NIJAVAM_API_URL', 'https://api-sandbox.ninjavan.co/sg/'),
    ],
    'nijavap' => [
        'api_url' => env('NIJAVAP_API_URL', 'https://api-sandbox.ninjavan.co/sg/'),
    ],

    'jnei' => [
        'api_url' => env('JNEI_API_URL', 'http://apiv2.jne.co.id:10102/'),
        'api_tracking' => env('JNEI_TRACK_URL', 'http://apiv2.jne.co.id:10102/'),
    ],

    'jntt' => [
        'api_url' => env('JNTT_API_URL', 'https://jtpay-uat.jtexpress.co.th/jts-tha-openplatform-api/'),
    ],

    'jnti' => [
        'api_url' => env('JNTI_API_URL', 'https://test-jk.jet.co.id/jts-idn-ecommerce-api/api/'),
        'api_tracking' => env('JNTI_API_TRACKING', 'http://test-jk.jet.co.id/jandt-order-ifd-web/'),
    ],

    'flash' => [
        'api_url' => env('FLASH_API_URL', 'https://open-api-tra.flashexpress.com'),
    ],

    'gge' => [
        'api_url' => env('GGE_API_URL', 'https://api.staging.gogoxpress.com/v1'),
    ],

    'application' => [
        'token_expire' => env('APPLICATION_TOKEN_EXPIRE', 365 * 24 * 60), // minutes
    ],

    'order' => [
        'stamp_token_expire' => env('ORDER_STAMP_TOKEN_EXPIRE', 60 * 60), // seconds
    ],
    'jntm' => [
        'api_url' => env('JNTM_API_URL', 'http://47.57.89.30'),
    ],

    'jntc' => [
        'api_url' => env('JNTC_API_URL', 'http://47.57.86.134/jandt-khm-api/api/'),
    ],

];
