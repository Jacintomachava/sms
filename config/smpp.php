<?php

return [
    'host' => env('SMPP_HOST', '10.229.35.80'),
    'port' => (int) env('SMPP_PORT', 8014),
    'system_id' => env('SMPP_SYSTEM_ID'),
    'password' => env('SMPP_PASSWORD'),
    'sender_id' => env('SMPP_SENDER_ID', 'InfoSMS'),
    'connect_timeout' => (int) env('SMPP_CONNECT_TIMEOUT', 10),
    'read_timeout' => (int) env('SMPP_READ_TIMEOUT', 5),
];



