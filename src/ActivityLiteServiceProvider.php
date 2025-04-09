<?php

namespace Garcia1901l\LaravelActivityLite;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;

class ActivityLiteServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->configureSqliteConnection();

        if ($this->app->runningInConsole()) {
            $this->publishResources();
            $this->registerCommands();
        }
    }

    protected function configureSqliteConnection()
    {
        $databasePath = storage_path(
            'logs/' . Config::get('activity-lite.database_name', 'activity_lite') . '.sqlite'
        );

        Config::set('database.connections.activity_lite', [
            'driver' => 'sqlite',
            'database' => $databasePath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
    }

    protected function publishResources()
    {
        $this->publishes([
            __DIR__.'/../config/activity-lite.php' => config_path('activity-lite.php'),
        ], 'activity-lite-config');
    }

    protected function registerCommands()
    {
        $this->commands([
            Console\Commands\InstallCommand::class,
            Console\Commands\CleanActivityCommand::class,
            Console\Commands\QueryLogsCommand::class,
        ]);
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/activity-lite.php', 'activity-lite');

        $this->app->bind('activity-lite.migrations_path', function () {
            // Si el paquete está en vendor (instalado vía Composer)
            if (file_exists(base_path('vendor/garcia1901l/laravel-activity-lite'))) {
                return 'vendor/garcia1901l/laravel-activity-lite/database/migrations';
            }
            
            // Si el paquete está en packages (desarrollo local)
            return 'packages/garcia1901l/laravel-activity-lite/database/migrations';
        });
    }
}