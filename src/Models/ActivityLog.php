<?php

namespace Garcia1901l\LaravelActivityLite\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

class ActivityLog extends Model
{
    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'activity_lite';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];
    
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'data' => 'array'
    ];

    /**
     * Create a new Eloquent model instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        // Asegurarse que la conexión existe
        if (!Config::has('database.connections.activity_lite')) {
            $this->configureDefaultConnection();
        }

        parent::__construct($attributes);
    }

    /**
     * Configure the default SQLite connection.
     */
    protected function configureDefaultConnection()
    {
        Config::set('database.connections.activity_lite', [
            'driver' => 'sqlite',
            'database' => storage_path('logs/activity_lite.sqlite'),
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);
    }

    public function model()
    {
        return $this->morphTo();
    }

    public function causer()
    {
        return $this->morphTo();
    }
}