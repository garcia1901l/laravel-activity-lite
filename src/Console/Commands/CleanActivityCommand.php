<?php

namespace Garcia1901l\LaravelActivityLite\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Garcia1901l\LaravelActivityLite\Models\ActivityLog;

class CleanActivityCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activity-lite:clean 
                            {--days= : Delete records older than X days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old activity logs';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        // Retrieve the number of days from the command option or use the default from config
        $days = $this->option('days') ?? config('activity-lite.clean_after_days', 365);
        
        // Calculate the cutoff date based on the number of days
        $cutoffDate = Carbon::now()->subDays($days);

        // Delete records older than the cutoff date
        $count = ActivityLog::where('created_at', '<', $cutoffDate)
            ->delete();

        // Inform the user about the number of deleted records
        $this->info("Deleted {$count} activity log(s) older than {$days} days.");

        if ($count === 0) {
            $this->line('No records found older than '.$days.' days.');
        }
    }
}