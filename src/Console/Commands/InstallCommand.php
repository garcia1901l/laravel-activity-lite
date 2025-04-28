<?php

namespace Garcia1901l\LaravelActivityLite\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class InstallCommand extends Command
{
    protected $signature = 'activity-lite:install {--test : Test connection after installation}';

    protected $description = 'Installs the Laravel Activity Lite package with MongoDB';

    public function handle()
    {
        $this->showWelcomeMessage();

        if (!$this->checkRequirements()) {
            $this->error('✖ Requirements not met. Installation cancelled.');
            return defined('Command::FAILURE') ? Command::FAILURE : 1;
        }

        $this->configureMongoDB();

        if ($this->option('test') && !$this->testConnection()) {
            return defined('Command::FAILURE') ? Command::FAILURE : 1;
        }

        $this->showSuccessMessage();
        return defined('Command::SUCCESS') ? Command::SUCCESS : 0;
    }

    protected function showWelcomeMessage(): void
    {
        $this->line('');
        $this->line('═══════════════════════════════════════════════════');
        $this->line('  Laravel Activity Lite - Installation with MongoDB  ');
        $this->line('═══════════════════════════════════════════════════');
        $this->line('');
    }

    protected function checkRequirements(): bool
    {
        $this->info('Checking requirements...');

        $requirementsMet = true;

        if (!extension_loaded('mongodb')) {
            $this->error('✖ MongoDB extension for PHP is not installed');
            $this->line('  To install:');
            $this->line('  - Windows: Download php_mongodb.dll for your PHP version');
            $this->line('  - Linux/Mac: Run "pecl install mongodb"');
            $this->line('  Then add "extension=mongodb" to your php.ini');
            $requirementsMet = false;
        } else {
            $this->info('✓ MongoDB extension for PHP is installed');
        }

        if (!class_exists(\MongoDB\Laravel\MongoDBServiceProvider::class)) {
            $this->error('✖ Package mongodb/laravel-mongodb is not installed');
            $this->line('  Run: composer require mongodb/laravel-mongodb');
            $requirementsMet = false;
        } else {
            $this->info('✓ Laravel MongoDB package is installed');
        }

        return $requirementsMet;
    }

    protected function configureMongoDB(): void
    {
        $this->info('Configuring MongoDB connection...');

        $config = [
            'driver' => 'mongodb',
            'host' => config('activity-lite.database.mongodb.host', 'localhost'),
            'port' => config('activity-lite.database.mongodb.port', 27017),
            'database' => config('activity-lite.database.name', 'activity_lite'),
            'username' => config('activity-lite.database.mongodb.username'),
            'password' => config('activity-lite.database.mongodb.password'),
            'options' => [
                'database' => config('activity-lite.database.mongodb.auth_db', 'admin')
            ]
        ];

        Config::set('database.connections.activity_lite', $config);
        $this->updateEnvFile($config);
        $this->info('✓ Connection successfully configured');
    }

    protected function testConnection(): bool
    {
        $this->info('Testing basic connection to MongoDB...');

        try {
            $connection = DB::connection('activity_lite');
            
            $connection->getMongoClient()->selectDatabase(
                config('activity-lite.database.name')
            )->command(['ping' => 1]);
            
            $this->info('✓ Successfully connected to MongoDB');
            return true;
            
        } catch (\Exception $e) {
            $this->error('✖ Connection error: '.$e->getMessage());
            return false;
        }
    }   

    protected function updateEnvFile(array $config): void
    {
        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            $this->warn('.env file not found. Configuration not permanently saved.');
            return;
        }

        $envUpdates = [
            'ACTIVITY_LITE_DB_NAME' => $config['database'],
            'ACTIVITY_LITE_MONGODB_HOST' => $config['host'],
            'ACTIVITY_LITE_MONGODB_PORT' => $config['port'],
            'ACTIVITY_LITE_MONGODB_USERNAME' => $config['username'] ?? '',
            'ACTIVITY_LITE_MONGODB_PASSWORD' => $config['password'] ?? '',
            'ACTIVITY_LITE_MONGODB_AUTH_DB' => $config['options']['database'] ?? 'admin'
        ];

        $envContent = file_get_contents($envPath);
        $updated = false;

        foreach ($envUpdates as $key => $value) {
            $value = $this->sanitizeEnvValue($value);

            if (strpos($envContent, "$key=") !== false) {
                $envContent = preg_replace(
                    "/^{$key}=.*/m",
                    "{$key}={$value}",
                    $envContent
                );
            } else {
                $envContent .= PHP_EOL . "{$key}={$value}";
                $updated = true;
            }
        }

        if ($updated) {
            file_put_contents($envPath, $envContent);
            $this->info('✓ .env file updated');
        }
    }

    protected function sanitizeEnvValue($value): string
    {
        if (empty($value)) {
            return '';
        }

        if (preg_match('/[\s#\$\'"]/', $value)) {
            return '"' . addslashes($value) . '"';
        }

        return $value;
    }

    protected function showSuccessMessage(): void
    {
        $config = config('activity-lite.database.mongodb');
        
        $this->line("\n".'═══════════════════════════════════════════════════');
        $this->info('  Installation completed successfully!');
        $this->line('═══════════════════════════════════════════════════'."\n");
        $this->line('MongoDB Configuration:');
        $this->line('- Database: '.config('activity-lite.database.name'));
        $this->line('- Host: '.($config['host'] ?? 'localhost'));
        $this->line('- Port: '.($config['port'] ?? 27017)."\n");
        $this->line('You can modify these values in:');
        $this->line('- config/activity-lite.php');
        $this->line('- .env'."\n");
    }
}
