<?php

return [
    'cache' => env('PW_CACHE', false),
    'host'  => env('PW_HOST', '127.0.0.1'),
    'port'  => env('PW_PORT', '3737'),
    'name'  => env('PW_NAME', 'phpwoo'),
    'resets' => [
        'auth',
        'auth.driver',
    ],
    'swoole' => [
        'max_request' => 3000,
        'user'        => '_www',
        'group'       => 'staff',
        // 'document_root' => '/Users/donald/Sites/swoole/public',
        // 'enable_static_handler' => true,
    ],
];
