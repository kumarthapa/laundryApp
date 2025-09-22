<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Specify the default filesystem disk that should be used by the framework.
    | The "local" disk, as well as multiple custom local disks, are available.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Configure as many filesystem "disks" as you like. Each disk is a storage
    | location on your local server. Supported drivers: "local", "ftp", "sftp".
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL') . '/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        'attachments' => [
            'driver' => 'local',
            'root' => storage_path('app/attachments'),
            'url' => env('APP_URL') . '/attachments',
            'visibility' => 'public',
            'throw' => false,
        ],

        'zips' => [
            'driver' => 'local',
            'root' => storage_path('app/zips'),
            'url' => env('APP_URL') . '/zips',
            'visibility' => 'public',
            'throw' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Define the symbolic links that will be created when running `php artisan
    | storage:link`. Keys are link locations in `public/` and values are targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
        public_path('attachments') => storage_path('app/attachments'),
        public_path('zips') => storage_path('app/zips'),
    ],

];