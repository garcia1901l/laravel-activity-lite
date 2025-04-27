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
        $this->info('Verificando requisitos...');

        $requirementsMet = true;

        // Verificar extensión MongoDB
        if (!extension_loaded('mongodb')) {
            $this->error('✖ Extensión MongoDB para PHP no está instalada');
            $this->line('  Para instalar:');
            $this->line('  - Windows: Descargar php_mongodb.dll para tu versión de PHP');
            $this->line('  - Linux/Mac: Ejecutar "pecl install mongodb"');
            $this->line('  Luego agregar "extension=mongodb" a tu php.ini');
            $requirementsMet = false;
        } else {
            $this->info('✓ Extensión MongoDB para PHP está instalada');
        }

        // Verificar paquete jenssegers/mongodb
        if (!class_exists(\MongoDB\Laravel\MongoDBServiceProvider::class)) {
            $this->error('✖ Paquete mongodb/laravel-mongodb no está instalado');
            $this->line('  Ejecuta: composer require mongodb/laravel-mongodb');
            $requirementsMet = false;
        } else {
            $this->info('✓ Paquete Laravel MongoDB está instalado');
        }

        return $requirementsMet;
    }

    protected function getDatabaseName(): string
    {
        $defaultName = config('activity-lite.database.name', 'activity_lite');

        return $this->option('name') ?? $this->ask(
            'Nombre de la base de datos MongoDB',
            $defaultName
        );
    }

    protected function getMongoConfig(string $dbName): array
    {
        return [
            'driver' => 'mongodb',
            'host' => config('activity-lite.database.mongodb.host', 'localhost'),
            'port' => config('activity-lite.database.mongodb.port', 27017),
            'database' => $dbName,
            'username' => config('activity-lite.database.mongodb.username'),
            'password' => config('activity-lite.database.mongodb.password'),
            'options' => [
                'database' => config('activity-lite.database.mongodb.auth_db', 'admin')
            ]
        ];
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
        $this->info('Probando conexión básica a MongoDB...');

        try {
            $connection = DB::connection('activity_lite');
            
            // Método más compatible para verificar conexión
            $connection->getMongoClient()->selectDatabase(
                config('activity-lite.database.name')
            )->command(['ping' => 1]);
            
            $this->info('✓ Conexión exitosa a MongoDB');
            return true;
            
        } catch (\Exception $e) {
            $this->error('✖ Error de conexión: '.$e->getMessage());
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
        
        $this->line("\n".'═══════════════════════════════════════════════════');
        $this->info('  Instalación completada con éxito!');
        $this->line('═══════════════════════════════════════════════════'."\n");
        $this->line('Configuración MongoDB:');
        $this->line('- Base de datos: '.config('activity-lite.database.name'));
        $this->line('- Host: '.($config['host'] ?? 'localhost'));
        $this->line('- Puerto: '.($config['port'] ?? 27017)."\n");
        $this->line('Puedes modificar estos valores en:');
        $this->line('- config/activity-lite.php');
        $this->line('- .env'."\n");
    }
}
