<?php

return [
    'default' => env('FILESYSTEM_DISK', 'private'),
    'disks' => [
        // Private disk: NOT web accessible. Files are only streamed through authorised API endpoints.
        'private' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => false,
            'throw' => true,
            'report' => false,
            'visibility' => 'private',
        ],
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => false,
            'throw' => false,
            'report' => false,
        ],
    ],
    'links' => [],
];
