<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('accreditation:process-expiries')->dailyAt('08:00');
Schedule::command('drafts:clean-expired')->dailyAt('02:00');
Schedule::command('audit:archive-old-logs')->yearlyOn(1, 1, '01:30');

// Additional Automated Tasks
Schedule::command('levy:send-reminders')->yearlyOn(3, 1, '08:00');
Schedule::command('portal:intake-automation --commit')->everyTenMinutes();
Schedule::command('system:monitor')->everyMinute()->withoutOverlapping(2);
