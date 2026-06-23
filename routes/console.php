<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Phase 7: surface due follow-up reminders into the in-app notification centre.
// Requires the system scheduler to be running (`php artisan schedule:run` via cron).
Schedule::command('leads:send-follow-up-reminders')->hourly();
