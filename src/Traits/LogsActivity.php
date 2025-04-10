<?php

namespace Garcia1901l\LaravelActivityLite\Traits;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\SoftDeletes;
use Garcia1901l\LaravelActivityLite\Models\ActivityLog;

trait LogsActivity
{
    protected static $logging = false;

    public static function bootLogsActivity()
    {
        if (self::isModelExcluded()) {
            return;
        }

        static::created(function ($model) {
            $model->logAction('created');
        });

        static::updated(function ($model) {
            $changes = $model->getDirty();
            if (array_key_exists('deleted_at', $changes) && count($changes) === 1) {
                return;
            }
            $model->logAction('updated');
        });

        static::deleted(function ($model) {
            // Determinar el tipo de acción de eliminación
            $usesSoftDeletes = in_array(
                SoftDeletes::class, 
                class_uses_recursive($model)
            );

            if ($usesSoftDeletes) {
                $action = $model->isForceDeleting() ? 'force_deleted' : 'soft_deleted';
            } else {
                $action = 'deleted'; 
            }

            $model->logAction($action);
        });

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class))) {
            static::restored(function ($model) {
                $model->logAction('restored');
            });
        }
    }

    protected static function isModelExcluded(): bool
    {
        $excludedModels = Config::get('activity-lite.except', []);
        return in_array(static::class, $excludedModels);
    }

    protected function logAction(string $action)
    {
        // Verificar si el logging está deshabilitado globalmente
        if (!Config::get('activity-lite.enabled', true)) {
            return;
        }

        // Verificar si el evento está configurado para ser registrado
        if (!in_array($action, Config::get('activity-lite.events', ['created', 'updated', 'deleted', 'soft_deleted', 'force_deleted', 'restored']))) {
            return;
        }

        static::$logging = true;

        try {
            ActivityLog::create([
                'action' => $action,
                'log_type' => 'model',
                'model_type' => get_class($this),
                'model_id' => $this->id,
                'causer_type' => $this->resolveCauserType(),
                'causer_id' => $this->resolveCauserId(),
                'data' => $this->getActivityData($action)
            ]);
        } finally {
            static::$logging = false;
        }
    }

    public static function logManualAction(string $action, array $data = []): void
    {
        if (!Config::get('activity-lite.enabled', true)) {
            return;
        }

        $model = new static; // Instancia del modelo actual

        ActivityLog::create([
            'action' => $action,
            'log_type' => 'manual', // ¡Aquí usamos 'manual' en lugar de 'model'!
            'model_type' => static::class,
            'model_id' => $model->id ?? null,
            'causer_type' => $model->resolveCauserType(),
            'causer_id' => $model->resolveCauserId(),
            'data' => $data // Datos personalizados
        ]);
    }

    protected function getActivityData(string $action): array
    {
        switch($action) {
            case 'created':
                return ['attributes' => $this->getAttributes()];
            case 'updated':
                return [
                    'changes' => $this->getDirty(), // Solo campos modificados (valores nuevos)
                    'old' => array_intersect_key($this->getOriginal(), $this->getDirty())
                ];
            case 'soft_deleted':
                return [
                    'attributes' => $this->getAttributes(),
                    'deleted_at' => now()->toDateTimeString()
                ];
            case 'force_deleted':
                return ['attributes' => $this->getAttributes()];
            case 'deleted':
                return ['attributes' => $this->getAttributes()];
            case 'restored':
                return [
                    'attributes' => $this->getAttributes(),
                    'restored_at' => now()->toDateTimeString()
                ];
            default:
                return [];
        }
    }

    protected function resolveCauserType(): ?string
    {
        // 1. Usuario autenticado
        if (auth()->check()) {
            return get_class(auth()->user()); // Cambiado de getMorphClass a get_class
        }

        // 2. Comando Artisan (incluye nombre del comando)
        if (app()->runningInConsole()) {
            $command = $this->getArtisanCommandName();
            return $command ? 'system:artisan:' . $command : 'system:artisan';
        }

        // 3. Job en cola (detecta nombre de la clase Job)
        if (app()->runningInQueue()) {
            return 'system:queue:' . $this->getJobClassName();
        }

        // 4. Petición API
        if (request()->is('api/*')) {
            return 'system:api';
        }

        // 5. Visitante web
        return 'system:guest';
    }

    protected function resolveCauserId(): ?int
    {
        return auth()->check() ? auth()->id() : null;
    }

    protected function getArtisanCommandName(): ?string
    {
        if (!app()->runningInConsole()) return null;
        
        return collect(request()->server('argv', []))
            ->first(fn($item) => Str::startsWith($item, 'artisan'));
    }

    protected function getJobClassName(): string
    {
        try {
            // Intenta obtener el nombre de la clase Job actual
            return get_class($this->job);
        } catch (\Throwable $e) {
            return 'unknown_job';
        }
    }
}
