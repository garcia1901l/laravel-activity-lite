<?php

namespace Garcia1901l\LaravelActivityLite\Console\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Garcia1901l\LaravelActivityLite\Models\ActivityLog;

class QueryLogsCommand extends Command
{
    protected $signature = 'activity-lite:query 
                            {--model= : Filter by model type (e.g. "User" or "App\Models\User")}
                            {--id= : Filter by specific model ID}
                            {--action= : Filter by action (created, updated, deleted, etc.)}
                            {--causer= : Filter by causer (type or ID)}
                            {--days= : Last N days (required if no other filters)}
                            {--json : Output as JSON}
                            {--csv : Export to CSV}
                            {--latest : Order by most recent first}
                            {--limit=50 : Limit number of results (default: 50, max: 500)}';

    protected $description = 'Query activity logs with safety limits and flexible filtering';

    // Máximos permitidos para evitar sobrecarga
    protected const MAX_LIMIT = 500;
    protected const DEFAULT_LIMIT = 50;
    protected const MAX_DAYS_UNFILTERED = 7;


    public function handle()
    {
        $query = ActivityLog::query();

        // Validaciones de seguridad
        if (!$this->hasFilters() && !$this->option('days')) {
            return $this->error('Safety measure: You must specify at least one filter or use --days option');
        }

        $this->applyFilters($query);
        $results = $query->get();

        if ($results->isEmpty()) {
            return $this->info('No logs found matching your criteria.');
        }

        $this->processResults($results);

        return Command::SUCCESS;
    }

    protected function hasFilters(): bool
    {
        return $this->option('model') ||
            $this->option('id') ||
            $this->option('action') ||
            $this->option('causer');
    }

    protected function applyFilters($query)
    {
        $limit = min(
            (int)$this->option('limit') ?: self::DEFAULT_LIMIT,
            self::MAX_LIMIT
        );
        $query->take($limit); // MongoDB usa take() en lugar de limit()

        // Filtro por modelo
        if ($model = $this->option('model')) {
            if (Str::contains($model, '\\')) {
                $query->where('model_type', $model);
            } else {
                $query->where('model_type', 'regexp', '/.*\\\\' . $model . '$/');
            }
        }

        // Filtro por ID
        if ($id = $this->option('id')) {
            $query->where('model_id', (int)$id); // Asegurar tipo numérico
        }

        // Filtro por acción
        if ($action = $this->option('action')) {
            $query->where('action', $action);
        }

        // Filtro por causante
        if ($causer = $this->option('causer')) {
            if (is_numeric($causer)) {
                $query->where('causer_id', (int)$causer);
            } else {
                $query->where('causer_type', 'regexp', '/.*' . $causer . '.*/');
            }
        }

        // Filtro por días
        if ($days = $this->option('days')) {
            $maxDays = $this->hasFilters() ? 365 : self::MAX_DAYS_UNFILTERED;
            $days = min((int)$days, $maxDays);
            $query->where('created_at', '>=', now()->subDays($days));
        }

        // Ordenamiento
        $orderDirection = $this->option('latest') ? 'desc' : 'asc';
        $query->orderBy('created_at', $orderDirection);
    }

    protected function processResults($results)
    {
        if ($this->option('json')) {
            return $this->line($results->toJson(JSON_PRETTY_PRINT));
        }

        if ($this->option('csv')) {
            return $this->exportToCsv($results);
        }

        $this->displayTableResults($results);
    }

    protected function displayTableResults($results)
    {
        $this->table(
            ['ID', 'Action', 'Model', 'Model ID', 'Causer', 'Causer ID', 'Date', 'Data'],
            $results->map(function ($item) {
                return [
                    $item->id,
                    $this->colorizeAction($item->action),
                    $this->formatModelType($item->model_type),
                    $item->model_id,
                    $this->formatCauser($item->causer_type),
                    $item->causer_id ?? 'N/A',
                    $item->created_at,
                    $this->formatData($item->data)
                ];
            })
        );
    }

    protected function colorizeAction($action)
    {
        $colors = [
            'created' => 'green',
            'updated' => 'yellow',
            'deleted' => 'red',
            'restored' => 'blue',
            'force_deleted' => 'magenta'
        ];

        $color = $colors[strtolower($action)] ?? 'white';
        return "<fg={$color}>{$action}</>";
    }

    protected function formatModelType($modelType)
    {
        return class_basename($modelType);
    }

    protected function formatCauser($causerType)
    {
        if (!$causerType) return '<fg=gray>System</>';

        if (Str::startsWith($causerType, 'App\\')) {
            return class_basename($causerType);
        }

        return $causerType;
    }

    protected function formatData($data)
    {
        // MongoDB ya devuelve los datos como array, no necesitamos decodificar JSON
        if (empty($data)) {
            return '<fg=gray>No data</>';
        }

        return collect($data)->map(function ($value, $key) {
            if (is_array($value)) {
                $value = json_encode($value, JSON_PRETTY_PRINT);
            }
            return "<fg=cyan>{$key}</>: {$value}";
        })->implode(', ');
    }

    protected function exportToCsv($results)
    {
        $filename = 'activity_logs_' . now()->format('Ymd_His') . '.csv';
        $path = storage_path('logs/' . $filename);

        $handle = fopen($path, 'w');

        // Headers
        fputcsv($handle, [
            'ID',
            'Action',
            'Model',
            'Model ID',
            'Causer',
            'Causer ID',
            'Date',
            'Data'
        ]);

        // Data
        foreach ($results as $row) {
            fputcsv($handle, [
                (string) $row->id,
                $row->action,
                $row->model_type,
                is_array($row->model_id) ? json_encode($row->model_id) : (string) $row->model_id,
                $row->causer_type ?? 'N/A',
                $row->causer_id ?? 'N/A',
                $row->created_at->toDateTimeString(),
                is_array($row->data) ? json_encode($row->data) : (string) $row->data
            ]);
        }

        fclose($handle);

        $this->info("CSV exported to: " . $path);
    }
}
