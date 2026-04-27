<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Process campaign recipients whose scheduled_at is due. Throttle is enforced
// by per-recipient scheduled_at; we only ever process 1 per minute.
Schedule::command('campaigns:process')->everyMinute()->withoutOverlapping();
