<?php

return [
    'admin' => [
        'username' => env('ADMIN_USERNAME', 'sam_yesayan'),
        'name' => env('ADMIN_NAME', 'Sam Yesayan'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    'trusted_proxies' => env('TRUSTED_PROXIES'),

    'uploads' => [
        'max_kb' => 25 * 1024,
        'max_files' => 10,
        'extensions' => [
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp',
            'txt', 'csv', 'rtf', 'md', 'json', 'zip', '7z', 'rar',
            'jpg', 'jpeg', 'png', 'webp', 'gif',
        ],
        'image_extensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
    ],
];
