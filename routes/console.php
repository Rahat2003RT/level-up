<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
Schedule::command('checklists:auto-close')->hourly();
Schedule::command('users:prune-deleted')->daily();
Schedule::command('reminders:process-contacts')->everyMinute();
