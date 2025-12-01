<?php declare(strict_types=1);

return [
    'api_versioning' => false,

    'default' => env('ELASTIC_CONNECTION', 'default'),

    'connections' => [
        'default' => [
            'hosts' => [
                // Pastikan ini menggunakan variabel yang sama dengan .env Anda
                env('ELASTIC_CLIENT_HOSTS', 'localhost:9200'),
            ],
        ],
    ],
];