<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Database File Name
    |--------------------------------------------------------------------------
    | Name of the SQLite file (will always be stored in storage/logs/)
    | Do not include extension! .sqlite will be added automatically
    */
    'database_name' => env('ACTIVITY_LITE_DB_NAME', 'activity_lite'), // Will generate activity_lite.sqlite

    /*
    |--------------------------------------------------------------------------
    | Automatic Cleanup
    |--------------------------------------------------------------------------
    */
    'clean_after_days' => env('ACTIVITY_LITE_CLEAN_DAYS', 365),

    /*
    |--------------------------------------------------------------------------
    | Enabled
    |--------------------------------------------------------------------------
    */
    'enabled' => env('ACTIVITY_LITE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Events to Log
    |--------------------------------------------------------------------------
    */
    'events' => ['created', 'updated', 'deleted', 'soft_deleted', 'force_deleted', 'restored'],

    /*
    |--------------------------------------------------------------------------
    | Models to Exclude
    |--------------------------------------------------------------------------
    */
    'except' => [
        // \App\Models\User::class,
    ],
];