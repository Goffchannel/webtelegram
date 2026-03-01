<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('service-access:send-expiry-reminders')->dailyAt('10:00');
Schedule::command('iptv:expire-accesses')->hourly();
Schedule::command('bot:send-scheduled-broadcasts')->everyMinute();
