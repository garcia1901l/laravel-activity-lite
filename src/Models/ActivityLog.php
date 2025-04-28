<?php

namespace Garcia1901l\LaravelActivityLite\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class ActivityLog extends Model
{
    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'activity_lite';

    /**
     * The collection associated with the model.
     *
     * @var string
     */
    protected $collection;

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
        'properties' => 'array'
    ];

    /**
     * Create a new Eloquent model instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        $this->collection = config('activity-lite.collection', 'activity_logs');
        parent::__construct($attributes);
    }

    /**
     * Get the related model.
     */
    public function subject()
    {
        return $this->morphTo('subject');
    }

    /**
     * Get the user that triggered the activity.
     */
    public function causer()
    {
        return $this->morphTo('causer');
    }

    /**
     * Scope a query to only include activities for a given causer.
     */
    public function scopeCausedBy($query, $causer)
    {
        return $query->where('causer_id', $causer->getKey())
                    ->where('causer_type', get_class($causer));
    }

    /**
     * Scope a query to only include activities for a given subject.
     */
    public function scopeForSubject($query, $subject)
    {
        return $query->where('subject_id', $subject->getKey())
                    ->where('subject_type', get_class($subject));
    }

    /**
     * Scope a query to only include activities of a given type.
     */
    public function scopeForEvent($query, string $eventType)
    {
        return $query->where('event', $eventType);
    }

    /**
     * Scope a query to only include activities from a given log.
     */
    public function scopeInLog($query, string $logName)
    {
        return $query->where('log_name', $logName);
    }

    /**
     * Scope a query to only include recent activities.
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}