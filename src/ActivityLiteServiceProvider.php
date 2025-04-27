<?php

namespace Garcia1901l\LaravelActivityLite;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;
use MongoDB\Laravel\Eloquent\Model;

class ActivityLiteServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/activity-lite.php', 'activity-lite');
        
        // Registrar la conexión MongoDB si no existe
        $this->configureDatabaseConnection();
        
        // Registrar el modelo ActivityLog con la conexión adecuada
        $this->app->bind(Model::class, function () {
            return new \Garcia1901l\LaravelActivityLite\Models\ActivityLog();
        });
    }

    public function boot()
    {
        $this->configureDatabaseConnection();
        
        if ($this->app->runningInConsole()) {
            $this->publishResources();
            $this->registerCommands();
        }
    }

    protected function configureDatabaseConnection()
    {
        // Solo configurar si no existe ya la conexión
        if (!Config::has('database.connections.activity_lite')) {
            Config::set('database.connections.activity_lite', [
                'driver' => 'mongodb',
                'host' => config('activity-lite.database.mongodb.host', 'localhost'),
                'port' => config('activity-lite.database.mongodb.port', 27017),
                'database' => config('activity-lite.database.name', 'activity_lite'),
                'username' => config('activity-lite.database.mongodb.username'),
                'password' => config('activity-lite.database.mongodb.password'),
                'options' => [
                    'database' => config('activity-lite.database.mongodb.auth_db', 'admin')
                ]
            ]);
        }
    }

    protected function publishResources()
    {
        $this->publishes([
            __DIR__.'/../config/activity-lite.php' => config_path('activity-lite.php'),
        ], 'activity-lite');
    }

    protected function registerCommands()
    {
        $this->commands([
            Console\Commands\InstallCommand::class,
            Console\Commands\CleanActivityCommand::class,
            Console\Commands\QueryLogsCommand::class,
        ]);
    }
}