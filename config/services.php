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

    'mpesa' => [
        'env' => env('MPESA_ENV'),
        'api_key' => env('MPESA_API_KEY'),
        'public_key' => env('MPESA_PUBLIC_KEY'),
        'service_provider_code' => env('MPESA_SERVICE_PROVIDER_CODE'),
    ],

    'smpp' => [
        'hosts' => array_values(array_filter(
            array_map(
                'trim',
                explode(',', env('SMPP_HOSTS', '10.229.35.80'))
            )
        )),

        'port' => (int) env('SMPP_PORT', 8014),

        'system_id' => env('SMPP_SYSTEM_ID'),
        'password' => env('SMPP_PASSWORD'),

        'timeout' => (int) env('SMPP_TIMEOUT', 10),
        'reconnect_delay' => (int) env('SMPP_RECONNECT_DELAY', 10),

        'tps' => (int) env('SMPP_TPS', 20),

        'window_size' => (int) env('SMPP_WINDOW_SIZE', 10),

        'heartbeat_interval' => (int) env('SMPP_HEARTBEAT_INTERVAL', 30),

        'heartbeat_timeout' => (int) env('SMPP_HEARTBEAT_TIMEOUT', 15),
    ],

    
    'sms_testes' => [
        'activo' => env('SMS_TESTES_ACTIVO', false),
        'limite_segmentos' => (int) env('SMS_TESTES_LIMITE', 1000),
        'sender' => env('SMS_TESTES_SENDER', 'infoSMS'),
    ],


  
];
