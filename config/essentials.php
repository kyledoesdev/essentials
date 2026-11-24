<?php

return [
    'timezone' => [
        'local_envs' => explode(',', (string) env('LOCAL_ENVS', 'local,development,dev')),
        'ttl' => (int) env('TIMEZONE_CACHE_TTL', 86400),
        'retry_after' => (int) env('TIMEZONE_RETRY_AFTER', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Backups
    |--------------------------------------------------------------------------
    |
    | `php artisan essentials:backup-database` dumps each connection, gzips it
    | and uploads it to {directory}/{timestamp}/{database}.sql.gz. When
    | `enabled` is true the command is added to the scheduler for you, using
    | `frequency` (a cron expression) in `timezone`.
    |
    | Leave `disk` empty to upload with the `s3` settings below, or name a disk
    | from config/filesystems.php to use that instead. Backups older than
    | `retention_days` are deleted after each run (0 keeps them forever).
    |
    */
    'db_backups' => [
        'enabled' => (bool) env('DB_BACKUP_ENABLED', false),
        'frequency' => env('DB_BACKUP_FREQUENCY', '30 20 * * *'),
        'timezone' => env('DB_BACKUP_TIMEZONE', 'UTC'),

        'connections' => array_values(array_filter(explode(',', (string) env('DB_BACKUP_CONNECTIONS', '')))),

        'disk' => env('DB_BACKUP_DISK'),
        's3' => [
            'key' => env('DB_BACKUP_AWS_ACCESS_KEY_ID', env('AWS_ACCESS_KEY_ID')),
            'secret' => env('DB_BACKUP_AWS_SECRET_ACCESS_KEY', env('AWS_SECRET_ACCESS_KEY')),
            'region' => env('DB_BACKUP_AWS_DEFAULT_REGION', env('AWS_DEFAULT_REGION', 'us-east-1')),
            'bucket' => env('DB_BACKUP_AWS_BUCKET'),
            'endpoint' => env('DB_BACKUP_AWS_ENDPOINT'),
            'use_path_style_endpoint' => (bool) env('DB_BACKUP_AWS_USE_PATH_STYLE_ENDPOINT', false),
        ],

        'directory' => env('DB_BACKUP_DIRECTORY', 'backups'),
        'folder_format' => env('DB_BACKUP_FOLDER_FORMAT', 'YmdHis'),
        'retention_days' => (int) env('DB_BACKUP_RETENTION_DAYS', 7),

        'log_channel' => env('DB_BACKUP_LOG_CHANNEL'),

        'temporary_directory' => env('DB_BACKUP_TEMPORARY_DIRECTORY', storage_path('app/essentials-backups')),

        'mysqldump' => [
            'binary' => env('DB_BACKUP_MYSQLDUMP_BINARY', 'mysqldump'),
            'timeout' => (int) env('DB_BACKUP_TIMEOUT', 900),
        ],
    ],
];
