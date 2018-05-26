<?php

return [
    'cache' => env('PW_CACHE', false),
    'host'  => env('PW_HOST', '127.0.0.1'),
    'port'  => env('PW_PORT', '3737'),
    'swoole' => [
        'max_request' => 3000,
        'user'        => '_www',
        'group'       => 'staff',
    ],
];