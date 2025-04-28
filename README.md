
# Laravel Activity Lite

[![Latest Version](https://img.shields.io/github/v/release/garcia1901l/laravel-activity-lite)](https://packagist.org/packages/garcia1901l/laravel-activity-lite)  
[![PHP Version](https://img.shields.io/badge/PHP-7.2.5%2B-blue)](https://php.net)  
[![Laravel Version](https://img.shields.io/badge/Laravel-7.x%20to%2012.x-orange)](https://laravel.com)  
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)  

Lightweight activity logger for Laravel using MongoDB (Laravel 10–12 compatible).

---

## ✨ Features

- 🚀 Automatic model activity tracking (create/update/delete)
- 🔍 MongoDB database for lightweight operation
- 👤 Tracks causer (user, artisan, queue jobs)
- ⚡ Configurable event logging
- 📊 Powerful querying capabilities
- 📦 Easy installation and setup

---

## Requisitos de MongoDB

The package is compatible with these versions of the official driver:

- `mongodb/laravel-mongodb`: 3.8+ (incluye v4 y v5)
- PHP extension `mongodb`: 1.10+

If you need to use a different version, install it manually:

```bash
composer require mongodb/laravel-mongodb:"YOUR_VERSION"
```

## 📦 Installation

### 1. Install via Composer

```bash
composer require garcia1901l/laravel-activity-lite
```

**Optional:** Publish the configuration file:

```bash
php artisan vendor:publish --provider="Garcia1901l\LaravelActivityLite\ActivityLiteServiceProvider"
```

### 2. Run the installation command

```bash
php artisan activity-lite:install
```

This will:

- Create the MongoDB database
- Run the required migrations

---

## 🧑‍💻 Basic Usage

Add the trait to your models:

```php
use Garcia1901l\LaravelActivityLite\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class User extends Model 
{
    use LogsActivity;
}
```

---

## 🔧 Advanced Usage

### Manual Logging

```php
User::logManualAction('custom_action', [
    'message' => 'Special event occurred',
    'data' => ['key' => 'value']
]);
```

### Query Logs

```bash
# View recent activity
php artisan activity-lite:query --days=7

# Filter by model
php artisan activity-lite:query --model=User

# Filter by model and id
php artisan activity-lite:query --model=User --id=5

# Filter by action
php artisan activity-lite:query --action=updated

# Export results
php artisan activity-lite:query --days=30 --json
php artisan activity-lite:query --days=30 --csv
```

---

## ⚙️ Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=activity-lite-config
```

Example of `config/activity-lite.php`:

```php
return [
    'enabled' => true,
    'database_name' => 'activity_lite',
    'events' => ['created', 'updated', 'deleted', 'soft_deleted', 'force_deleted', 'restored'],
    'except' => [], // Models to exclude
];
```

---

## 🗃️ Database Structure

The `activity_logs` table includes:

| Column       | Type      | Description                |
|--------------|-----------|----------------------------|
| id           | bigint    | Primary key                |
| action       | string    | Performed action           |
| log_type     | string    | 'model' or 'manual'        |
| model_type   | string    | Model class                |
| model_id     | bigint    | Model ID                   |
| causer_type  | string    | Who performed the action   |
| causer_id    | bigint    | Causer ID                  |
| data         | json      | Change data                |
| created_at   | timestamp | Creation time              |
| updated_at   | timestamp | Last update time           |

---

## 🛠️ Customization

### Temporarily disable logging

```php
config(['activity-lite.enabled' => false]);
```

### Exclude specific models

```php
'except' => [
    App\Models\SensitiveModel::class,
],
```

### Log only specific attributes and ignore others

```php
public function getActivityLogOptions(): array
{
    return [
        'log_attributes' => ['name', 'email'],
        'ignore_attributes' => ['password', 'remember_token']
    ];
}
```

---

## ✅ Requirements

- PHP 8.1+
- Laravel 10.x to 12.x
- MongoDB extension for PHP

---

## 📄 License

MIT License. See [LICENSE](LICENSE) for details.

---

## 👤 Author

Frank Garcia
