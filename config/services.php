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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'shopee' => [
        'env' => env('SHOPEE_ENV', 'test'),
        'partner_id' => env('SHOPEE_PARTNER_ID'),
        'partner_key' => env('SHOPEE_PARTNER_KEY'),
        'redirect_url' => env('SHOPEE_REDIRECT_URL'),
        'host_test' => env('SHOPEE_HOST_TEST', 'https://partner.test-stable.shopeemobile.com'),
        'host_prod' => env('SHOPEE_HOST_PROD', 'https://partner.shopeemobile.com'),
    ],

    'buku_tamu' => [
        'signature_disk' => env('BUKU_TAMU_SIGNATURE_DISK', 'signatures'),
    ],

    'internal_maintenance' => [
        'key' => env('MAINTENANCE_KEY'),
    ],

];
