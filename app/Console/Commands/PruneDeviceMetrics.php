<?php

namespace App\Console\Commands;

use App\Models\DeviceMetric;
use Illuminate\Console\Command;

class PruneDeviceMetrics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'metrics:prune';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete device_metrics rows older than the configured retention period';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $retentionDays = (int) config('monitoring.metrics_retention_days');
        $cutoff = now()->subDays($retentionDays);

        $deleted = DeviceMetric::where('polled_at', '<', $cutoff)->delete();

        $this->info("Deleted {$deleted} device_metrics row(s) older than {$retentionDays} days.");

        return self::SUCCESS;
    }
}
