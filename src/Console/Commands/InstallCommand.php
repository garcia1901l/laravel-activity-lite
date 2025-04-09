<?php

namespace Garcia1901l\LaravelActivityLite\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;

class InstallCommand extends Command
{
    protected $signature = 'activity-lite:install';
    protected $description = 'Install Laravel Activity Lite package with SQLite database';

    // Constants for repeated values
    const CONNECTION_NAME = 'activity_lite';
    const DEFAULT_DB_NAME = 'activity_lite';

    protected function getConfiguration(): array
    {
        return [
            'database_name' => config('activity-lite.database_name', self::DEFAULT_DB_NAME),
        ];
    }

    protected function checkRequirements(): bool
    {
        if (!extension_loaded('sqlite3')) {
            $this->error('SQLite3 extension is not enabled in your PHP installation.');
            $this->line('Please enable it in your php.ini and restart your web server.');
            return false;
        }

        return true;
    }

    protected function createSqliteDatabase(): bool
    {
        try {
            $config = $this->getConfiguration();
            $dbName = $config['database_name'] ?? self::DEFAULT_DB_NAME;

            if (empty($dbName)) {
                throw new \RuntimeException('Database name cannot be empty.');
            }

            $logsPath = storage_path('logs');
            $dbPath = "{$logsPath}/{$dbName}.sqlite";

            if (!File::exists($logsPath) && !File::makeDirectory($logsPath, 0755, true)) {
                throw new \RuntimeException("Cannot create directory: {$logsPath}");
            }

            if (!File::exists($dbPath)) {
                if (File::put($dbPath, '') === false) {
                    throw new \RuntimeException("Cannot create database file: {$dbPath}");
                }
                $this->info("SQLite database created at: {$dbPath}");
            } else {
                $this->info("SQLite database already exists at: {$dbPath}");
            }

            // Configure the database connection
            Config::set('database.connections.' . self::CONNECTION_NAME, [
                'driver' => 'sqlite',
                'database' => $dbPath,
                'prefix' => '',
                'foreign_key_constraints' => false,
            ]);

            return true;
        } catch (\Exception $e) {
            $this->error('Error creating SQLite database: ' . $e->getMessage());
            return false;
        }
    }

    protected function runMigrations(): bool
    {
        try {
           
            $this->call('migrate', [
                '--database' => self::CONNECTION_NAME,
                '--path' => app('activity-lite.migrations_path'),
                '--force' => true,
            ]);

            $this->info('Migrations completed successfully.');
            return true;
        } catch (\Exception $e) {
            $this->error('Error running migrations: ' . $e->getMessage());
            return false;
        }
    }

    public function handle()
    {
        $this->info('Setting up Laravel Activity Lite...');

        // Step 1: Check requirements
        if (!$this->checkRequirements()) {
            return Command::FAILURE;
        }

        // Step 2: Create SQLite database
        if (!$this->createSqliteDatabase()) {
            return Command::FAILURE;
        }

        // Step 3: Run migrations
        if (!$this->runMigrations()) {
            return Command::FAILURE;
        }

        $this->info('Package installed successfully!');
        return Command::SUCCESS;
    }
}