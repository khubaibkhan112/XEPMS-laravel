<?php

return [
    'default_timezone' => env('CHANNEL_MANAGER_DEFAULT_TIMEZONE', 'Europe/London'),
    'default_currency' => env('CHANNEL_MANAGER_DEFAULT_CURRENCY', 'GBP'),
    'default_locale' => env('CHANNEL_MANAGER_DEFAULT_LOCALE', 'en-GB'),

    'channels' => [
        'booking_com' => [
            'name' => 'Booking.com',
            'region' => 'UK',
            'supported_locales' => ['en-GB'],
            'supported_currencies' => ['GBP', 'EUR'],
            'api_version' => '2.5',
            'base_urls' => [
                'production' => env('BOOKING_COM_BASE_URL', 'https://distribution-xml.booking.com/json'),
                'sandbox' => env('BOOKING_COM_SANDBOX_URL', 'https://distribution-xml.booking.com/json'),
            ],
            'rate_limits' => [
                'requests_per_minute' => 60,
                'parallel_connections' => 4,
            ],
            'endpoints' => [
                'test_connection' => '/availability/status',
                'reservations' => '/reservations',
                'availability' => '/availability',
                'rates' => '/rates',
                'room_inventory' => '/room_inventory',
            ],
            'webhook' => [
                'signature_header' => 'X-Booking-Signature',
            ],
        ],
        'expedia' => [
            'name' => 'Expedia',
            'region' => 'UK',
            'supported_locales' => ['en-GB'],
            'supported_currencies' => ['GBP'],
            'api_version' => '1.0',
            'base_urls' => [
                'production' => env('EXPEDIA_BASE_URL', 'https://services.expediapartnercentral.com'),
                'sandbox' => env('EXPEDIA_SANDBOX_URL', 'https://sandbox.expediapartnercentral.com'),
            ],
            'rate_limits' => [
                'requests_per_minute' => 100,
                'parallel_connections' => 5,
            ],
            'endpoints' => [
                'test_connection' => '/availability/status',
                'reservations' => '/reservation/list',
                'availability' => '/availability/update',
                'rates' => '/rates/update',
                'room_inventory' => '/inventory/update',
            ],
            'webhook' => [
                'signature_header' => 'X-Expedia-Signature',
            ],
        ],
        'airbnb' => [
            'name' => 'Airbnb',
            'region' => 'UK',
            'supported_locales' => ['en-GB'],
            'supported_currencies' => ['GBP'],
            'api_version' => '1.0',
            'base_urls' => [
                'production' => env('AIRBNB_BASE_URL', 'https://connect.airbnb.com'),
                'sandbox' => env('AIRBNB_SANDBOX_URL', 'https://sandbox.connect.airbnb.com'),
            ],
            'rate_limits' => [
                'requests_per_hour' => 1000,
                'parallel_connections' => 5,
            ],
            'endpoints' => [
                'test_connection' => '/v1/ping',
                'reservations' => '/v1/reservations',
                'availability' => '/v1/availability',
                'rates' => '/v1/rates',
                'room_inventory' => '/v1/listings',
            ],
            'webhook' => [
                'signature_header' => 'X-Airbnb-Signature',
            ],
        ],
    ],
];

