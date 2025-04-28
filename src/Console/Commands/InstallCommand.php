<?php

namespace Garcia1901l\LaravelActivityLite\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class InstallCommand extends Command
{
    protected $signature = 'activity-lite:install {--test : Probar conexión después de instalar}';

    protected $description = 'Instala el paquete Laravel Activity Lite con MongoDB';

    public function handle()
    {
        $this->showWelcomeMessage();

        if (!$this->checkRequirements()) {
            $this->error('✖ Requisitos no cumplidos. Instalación cancelada.');
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
        $this->line('  Laravel Activity Lite - Instalación con MongoDB  ');
        $this->line('═══════════════════════════════════════════════════');
        $this->line('');
    }

    protected function checkRequirements(): bool
    {
        $this->info('Checking requirements...');

        $requirementsMet = true;
        $laravelVersion = app()->version();
        $mongoPackageVersion = $this->getRecommendedMongoVersion($laravelVersion);

        // Verificar extensión MongoDB
        if (!extension_loaded('mongodb')) {
            $this->error('✖ PHP MongoDB extension not installed');
            $this->line('  To install:');
            $this->line('  - Windows: Download php_mongodb.dll for your PHP version');
            $this->line('  - Linux/Mac: Run "pecl install mongodb"');
            $this->line('  Then add "extension=mongodb" to your php.ini');
            $requirementsMet = false;
        } else {
            $this->info('✓ PHP MongoDB extension installed');
        }

        // Verificar paquete jenssegers/mongodb
        if (!class_exists(\Jenssegers\Mongodb\MongodbServiceProvider::class)) {
            $this->error('✖ jenssegers/mongodb package not installed');
            $this->line("  Required version for Laravel {$laravelVersion}:");
            $this->line("  Run: composer require jenssegers/mongodb:{$mongoPackageVersion}");
            $requirementsMet = false;
        } else {
            $installedVersion = $this->getInstalledPackageVersion('jenssegers/mongodb');
            if (!$this->isVersionCompatible($installedVersion, $mongoPackageVersion)) {
                $this->error("✖ Incompatible jenssegers/mongodb version installed ({$installedVersion})");
                $this->line("  Required version for Laravel {$laravelVersion}: {$mongoPackageVersion}");
                $this->line("  Run: composer require jenssegers/mongodb:{$mongoPackageVersion}");
                $requirementsMet = false;
            } else {
                $this->info("✓ jenssegers/mongodb package installed (v{$installedVersion})");
            }
        }

        return $requirementsMet;
    }

    protected function getRecommendedMongoVersion(string $laravelVersion): string
    {
        // Extraer versión mayor de Laravel (ej: "7.x.x" → 7)
        $majorVersion = (int) explode('.', $laravelVersion)[0];

        // Reemplazamos el match (PHP 8+) con switch (PHP 7 compatible)
        switch ($majorVersion) {
            case 7:
                return '^3.7';  // Laravel 7
            case 8:
                return '^3.8';  // Laravel 8
            case 9:
                return '^3.9';  // Laravel 9
            default:
                return '^3.9'; // Default para versiones superiores
        }
    }

    protected function getInstalledPackageVersion(string $package): string
    {
        $composerLock = base_path('composer.lock');
        if (file_exists($composerLock)) {
            $data = json_decode(file_get_contents($composerLock), true);
            foreach ($data['packages'] ?? [] as $installed) {
                if ($installed['name'] === $package) {
                    return ltrim($installed['version'], 'v');
                }
            }
        }
        return 'unknown';
    }

    protected function isVersionCompatible(string $installedVersion, string $requiredPattern): bool
    {
        // Lógica simple de comparación (para implementación completa usar composer/semver)
        $requiredVersion = str_replace('^', '', $requiredPattern);
        return version_compare($installedVersion, $requiredVersion, '>=');
    }


    protected function configureMongoDB(): void
    {
        $this->info('Configurando conexión a MongoDB...');

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
        $this->info('✓ Conexión configurada correctamente');
    }

    protected function testConnection(): bool
    {
        $this->info('Testing MongoDB connection...');

        try {
            $connection = DB::connection('activity_lite');
            
            // Método más compatible para verificar conexión
            $connection->getMongoClient()->selectDatabase(
                config('activity-lite.database.name')
            )->command(['ping' => 1]);
            $this->info('✓ MongoDB connection successful');
            return true;
        } catch (\Exception $e) {
            $this->error('✖ Connection error: ' . $e->getMessage());
            return false;
        }
    }

    protected function updateEnvFile(array $config): void
    {
        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            $this->warn('Archivo .env no encontrado. Configuración no guardada permanentemente.');
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
            $this->info('✓ Archivo .env actualizado');
        }
    }

    protected function sanitizeEnvValue($value): string
    {
        if (empty($value)) {
            return '';
        }

        // Si contiene espacios o caracteres especiales, usar comillas
        if (preg_match('/[\s#\$\'"]/', $value)) {
            return '"' . addslashes($value) . '"';
        }

        return $value;
    }

    protected function showSuccessMessage(): void
    {
        $config = config('activity-lite.database.mongodb');

        $this->line("\n" . '═══════════════════════════════════════════════════');
        $this->info('  Instalación completada con éxito!');
        $this->line('═══════════════════════════════════════════════════' . "\n");
        $this->line('Configuración MongoDB:');
        $this->line('- Base de datos: ' . config('activity-lite.database.name'));
        $this->line('- Host: ' . ($config['host'] ?? 'localhost'));
        $this->line('- Puerto: ' . ($config['port'] ?? 27017) . "\n");
        $this->line('Puedes modificar estos valores en:');
        $this->line('- config/activity-lite.php');
        $this->line('- .env' . "\n");
    }
}
