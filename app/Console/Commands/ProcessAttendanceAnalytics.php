<?php

namespace App\Console\Commands;

use App\Services\AttendanceAnalyticsService;
use Illuminate\Console\Command;

class ProcessAttendanceAnalytics extends Command
{
    protected $signature = 'analytics:process-attendance';
    protected $description = 'Snapshot required audiences and finalize attendance analytics for due events';

    public function handle(AttendanceAnalyticsService $service): int
    {
        $result = $service->processDueEvents();
        $this->info("Snapshotted {$result['snapshotted']} event(s); finalized {$result['finalized']} event(s).");

        return self::SUCCESS;
    }
}
