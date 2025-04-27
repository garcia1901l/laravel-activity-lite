<?php

return [
   'collection' => env('ACTIVITY_LITE_COLLECTION', 'activity_logs'),
    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    */
   'database' => [
        'name' => env('ACTIVITY_LITE_DB_NAME', 'activity_lite'),
        'mongodb' => [
            'host' => env('ACTIVITY_LITE_MONGODB_HOST', 'localhost'),
            'port' => env('ACTIVITY_LITE_MONGODB_PORT', 27017),
            'username' => env('ACTIVITY_LITE_MONGODB_USERNAME'),
            'password' => env('ACTIVITY_LITE_MONGODB_PASSWORD'),
            'auth_db' => env('ACTIVITY_LITE_MONGODB_AUTH_DB', 'admin'),
        ],
    ],

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