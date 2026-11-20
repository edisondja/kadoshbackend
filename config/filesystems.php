<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DRIVER', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Default Cloud Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Many applications store files both locally and in the cloud. For this
    | reason, you may specify a default "cloud" driver here. This driver
    | will be bound as the Cloud disk implementation in the container.
    |
    */

    'cloud' => env('FILESYSTEM_CLOUD', 'spaces'),

    /*
    |--------------------------------------------------------------------------
    | Disco de medios (fotos, radiografías, logos)
    |--------------------------------------------------------------------------
    | Usa DigitalOcean Spaces cuando hay DO_SPACES_KEY; si no, disco public local.
    */
    'media' => env('FILESYSTEM_MEDIA_DISK', env('DO_SPACES_KEY') ? 'spaces' : 'public'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported Drivers: "local", "ftp", "s3", "rackspace"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
        ],

        /*
        | DigitalOcean Spaces — bucket graned (sfo3)
        | Endpoint API: https://sfo3.digitaloceanspaces.com
        | URL pública:  https://graned.sfo3.digitaloceanspaces.com
        */
        'spaces' => [
            'driver' => 's3',
            'key' => env('DO_SPACES_KEY'),
            'secret' => env('DO_SPACES_SECRET'),
            'region' => env('DO_SPACES_REGION', 'sfo3'),
            'bucket' => env('DO_SPACES_BUCKET', 'graned'),
            'endpoint' => env('DO_SPACES_ENDPOINT', 'https://sfo3.digitaloceanspaces.com'),
            'url' => (static function () {
                $cdn = trim((string) env('DO_SPACES_CDN_URL', ''));
                if ($cdn !== '') {
                    return $cdn;
                }
                $url = trim((string) env('DO_SPACES_URL', ''));
                return $url !== '' ? $url : 'https://graned.sfo3.digitaloceanspaces.com';
            })(),
            'use_path_style_endpoint' => env('DO_SPACES_PATH_STYLE', false),
            'throw' => false,
            'visibility' => 'public',
            'options' => [
                'ACL' => 'public-read',
            ],
        ],

    ],

];
