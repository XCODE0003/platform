<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Fallback TP/SL check — every minute in case a BarUpdated event was missed
Schedule::command('trade:check-positions')->everyMinute()->withoutOverlapping();

// Keep exchange rates current for portfolio valuation
Schedule::command('rates:update')->everyMinute()->withoutOverlapping();
